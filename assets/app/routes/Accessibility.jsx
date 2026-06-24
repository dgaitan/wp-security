import { useState, useRef, useCallback } from '@wordpress/element';
import apiFetch from '@wordpress/api-fetch';
import { __ } from '@wordpress/i18n';
import axe from 'axe-core';
import { FindingItem } from '../components/FindingItem';

const IMPACT_TO_SEVERITY = {
	critical: 'critical',
	serious:  'high',
	moderate: 'medium',
	minor:    'low',
};

function mapViolation( violation ) {
	return {
		check_id:       `accessibility.${ violation.id }`,
		status:         'fail',
		severity:       IMPACT_TO_SEVERITY[ violation.impact ] ?? 'medium',
		title:          violation.description,
		description:    violation.help,
		recommendation: violation.helpUrl,
		evidence:       { nodes: violation.nodes.length, tags: violation.tags },
	};
}

export function Accessibility() {
	const [ isRunning, setIsRunning ] = useState( false );
	const [ findings, setFindings ] = useState( null );
	const [ auditError, setAuditError ] = useState( null );
	const iframeRef = useRef( null );

	const runAudit = useCallback( () => {
		setIsRunning( true );
		setAuditError( null );

		const homeUrl = window.wpSecurityData?.homeUrl ?? window.location.origin;

		const iframe = document.createElement( 'iframe' );
		iframe.setAttribute( 'title', __( 'Accessibility audit sandbox', 'wp-security' ) );
		iframe.style.cssText = 'position:absolute;width:1280px;height:768px;left:-9999px;top:-9999px;';
		iframeRef.current = iframe;

		iframe.addEventListener( 'load', () => {
			try {
				const iDoc = iframe.contentDocument ?? iframe.contentWindow?.document;
				if ( ! iDoc ) {
					throw new Error( 'Cannot access iframe document' );
				}

				// Inject axe-core source into the sandboxed page.
				const script = iDoc.createElement( 'script' );
				script.text = axe.source;
				iDoc.head.appendChild( script );

				iframe.contentWindow.axe.run( iDoc, {}, ( err, results ) => {
					document.body.removeChild( iframe );
					iframeRef.current = null;

					if ( err ) {
						setAuditError( err.message );
						setIsRunning( false );
						return;
					}

					const converted = ( results.violations ?? [] ).map( mapViolation );

					apiFetch( {
						path:   '/wp-security/v1/findings/external',
						method: 'POST',
						data:   { module_id: 'accessibility', findings: converted },
					} ).then( () => {
						setFindings( converted );
						setIsRunning( false );
					} ).catch( ( fetchErr ) => {
						setAuditError( fetchErr.message ?? __( 'Could not save findings.', 'wp-security' ) );
						setIsRunning( false );
					} );
				} );
			} catch ( e ) {
				document.body.removeChild( iframe );
				iframeRef.current = null;
				setAuditError( e.message );
				setIsRunning( false );
			}
		} );

		iframe.addEventListener( 'error', () => {
			document.body.removeChild( iframe );
			iframeRef.current = null;
			setAuditError( __( 'Could not load the homepage for auditing.', 'wp-security' ) );
			setIsRunning( false );
		} );

		iframe.src = homeUrl;
		document.body.appendChild( iframe );
	}, [] );

	return (
		<div className="wpsec-section wrap">
			<h1>{ __( 'Accessibility', 'wp-security' ) }</h1>

			<p className="description">
				{ __( 'Runs axe-core against your homepage and reports WCAG 2.1 AA violations.', 'wp-security' ) }
			</p>

			<button
				className="button button-primary"
				onClick={ runAudit }
				disabled={ isRunning }
			>
				{ isRunning
					? __( 'Running audit…', 'wp-security' )
					: __( 'Run Accessibility Audit', 'wp-security' ) }
			</button>

			{ auditError && (
				<div className="notice notice-error">
					<p>{ auditError }</p>
				</div>
			) }

			{ findings !== null && findings.length === 0 && (
				<div className="notice notice-success">
					<p>{ __( 'No accessibility violations found. Great work!', 'wp-security' ) }</p>
				</div>
			) }

			{ findings && findings.length > 0 && (
				<>
					<p>
						{ /* translators: %d: number of violations */ }
						{ `${ findings.length } ${ findings.length === 1
							? __( 'violation found', 'wp-security' )
							: __( 'violations found', 'wp-security' ) }` }
					</p>
					<ul
						className="wpsec-findings-list"
						aria-label={ __( 'Accessibility findings', 'wp-security' ) }
					>
						{ findings.map( ( finding ) => (
							<FindingItem key={ finding.check_id } finding={ finding } />
						) ) }
					</ul>
				</>
			) }
		</div>
	);
}
