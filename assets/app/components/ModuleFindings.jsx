import { __ } from '@wordpress/i18n';
import { useQuery } from '@tanstack/react-query';
import { fetchModuleFindings } from '../api/modules';
import { FindingItem } from './FindingItem';

export function ModuleFindings( { moduleId, loadingMessage, errorMessage, emptyMessage, ariaLabel } ) {
    const { data: findings, isLoading, isError } = useQuery( {
		queryKey: [ 'module-findings', moduleId ],
		queryFn:  () => fetchModuleFindings( moduleId ),
	} );

    loadingMessage = loadingMessage ?? __( 'Loading findings…', 'wp-security' );
    errorMessage = errorMessage ?? __( 'Failed to load findings. Please try again.', 'wp-security' );
    emptyMessage = emptyMessage ?? __( 'No findings yet. Run a scan to audit your findings.', 'wp-security' );
    ariaLabel = ariaLabel ?? __( 'Findings', 'wp-security' );

    return (
        <>
            { isLoading && (
				<p>{ loadingMessage }</p>
			) }

			{ isError && (
				<p className="wpsec-findings__error">
					{ errorMessage }
				</p>
			) }

			{ ! isLoading && ! isError && ( ! findings || findings.length === 0 ) && (
				<p className="wpsec-findings__empty">
					{ emptyMessage }
				</p>
			) }

			{ findings && findings.length > 0 && (
				<ul
					className="wpsec-findings-list"
					aria-label={ ariaLabel }
				>
					{ findings.map( ( finding ) => (
						<FindingItem key={ finding.check_id } finding={ finding } />
					) ) }
				</ul>
			) }
        </>
    );
}