import { useState, useEffect } from '@wordpress/element';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchSettings, updateSettings } from '../api/settings';

export function Settings() {
	const queryClient = useQueryClient();
	const [ saved, setSaved ] = useState( false );
	const [ provider, setProvider ] = useState( 'wpvulnerability' );
	const [ apiKey, setApiKey ] = useState( '' );

	const { data: settings, isLoading, isError } = useQuery( {
		queryKey: [ 'settings' ],
		queryFn:  fetchSettings,
	} );

	// Sync local state when settings load.
	useEffect( () => {
		if ( settings ) {
			setProvider( settings.vuln_advisor_provider ?? 'wpvulnerability' );
			setApiKey( settings.wpscan_api_key ?? '' );
		}
	}, [ settings ] );

	const mutation = useMutation( {
		mutationFn: updateSettings,
		onSuccess:  () => {
			queryClient.invalidateQueries( { queryKey: [ 'settings' ] } );
			setSaved( true );
			setTimeout( () => setSaved( false ), 3000 );
		},
	} );

	if ( isLoading ) {
		return (
			<div className="wpsec-section wrap">
				<p>{ __( 'Loading settings…', 'wp-security' ) }</p>
			</div>
		);
	}

	if ( isError ) {
		return (
			<div className="wpsec-section wrap">
				<div className="notice notice-error">
					<p>
						{ __(
							'Could not load settings. Please reload the page.',
							'wp-security'
						) }
					</p>
				</div>
			</div>
		);
	}

	function handleSubmit( e ) {
		e.preventDefault();
		const payload = { vuln_advisor_provider: provider };
		if ( provider === 'wpscan' ) {
			payload.wpscan_api_key = apiKey;
		}
		mutation.mutate( payload );
	}

	return (
		<div className="wpsec-section wrap">
			<h1 className="wpsec-section__title">
				{ __( 'Settings', 'wp-security' ) }
			</h1>

			{ saved && (
				<div className="notice notice-success is-dismissible">
					<p>{ __( 'Settings saved.', 'wp-security' ) }</p>
				</div>
			) }

			{ mutation.isError && (
				<div className="notice notice-error">
					<p>
						{ __(
							'Could not save settings. Please try again.',
							'wp-security'
						) }
					</p>
				</div>
			) }

			<form className="wpsec-settings-form" onSubmit={ handleSubmit }>
				<table className="form-table" role="presentation">
					<tbody>
						<tr>
							<th scope="row">
								<label>
									{ __( 'Vulnerability Advisor', 'wp-security' ) }
								</label>
							</th>
							<td>
								<fieldset>
									<legend className="screen-reader-text">
										{ __( 'Vulnerability Advisor', 'wp-security' ) }
									</legend>
									{ Object.entries(
										settings?.available_providers ?? {}
									).map( ( [ slug, name ], i ) => (
										<span key={ slug }>
											{ i > 0 && <br /> }
											<label className="wpsec-settings-form__radio-label">
												<input
													type="radio"
													name="vuln_advisor_provider"
													value={ slug }
													checked={ provider === slug }
													onChange={ () => setProvider( slug ) }
												/>
												{ ' ' }
												{ name }
											</label>
										</span>
									) ) }
								</fieldset>

								{ provider === 'wpscan' && (
									<div className="wpsec-settings-form__api-key">
										<label
											htmlFor="wpscan_api_key"
											className="wpsec-settings-form__label"
										>
											{ __( 'WPScan API Key', 'wp-security' ) }
										</label>
										<input
											id="wpscan_api_key"
											name="wpscan_api_key"
											type="text"
											className="regular-text"
											value={ apiKey }
											onChange={ ( e ) => setApiKey( e.target.value ) }
											placeholder={ __( 'Paste your WPScan token here', 'wp-security' ) }
										/>
										<p className="description">
											{ __( 'Obtain a token at wpscan.com/register.', 'wp-security' ) }
										</p>
									</div>
								) }
							</td>
						</tr>
					</tbody>
				</table>

				<p className="submit">
					<button
						type="submit"
						className="button button-primary"
						disabled={ mutation.isPending }
					>
						{ mutation.isPending
							? __( 'Saving…', 'wp-security' )
							: __( 'Save Settings', 'wp-security' ) }
					</button>
				</p>
			</form>
		</div>
	);
}
