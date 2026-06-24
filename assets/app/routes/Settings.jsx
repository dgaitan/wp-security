import { useState, useEffect } from '@wordpress/element';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchSettings, updateSettings } from '../api/settings';

export function Settings() {
	const queryClient = useQueryClient();
	const [ saved, setSaved ]               = useState( false );
	const [ provider, setProvider ]         = useState( 'wpvulnerability' );
	const [ apiKey, setApiKey ]             = useState( '' );
	const [ frequency, setFrequency ]       = useState( 'daily' );
	const [ alertEmail, setAlertEmail ]     = useState( '' );
	const [ slackUrl, setSlackUrl ]         = useState( '' );

	const { data: settings, isLoading, isError } = useQuery( {
		queryKey: [ 'settings' ],
		queryFn:  fetchSettings,
	} );

	// Sync local state when settings load.
	useEffect( () => {
		if ( settings ) {
			setProvider( settings.vuln_advisor_provider ?? 'wpvulnerability' );
			setApiKey( settings.wpscan_api_key ?? '' );
			setFrequency( settings.scan_frequency ?? 'daily' );
			setAlertEmail( settings.alert_email ?? '' );
			setSlackUrl( settings.slack_webhook_url ?? '' );
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
		const payload = {
			vuln_advisor_provider: provider,
			scan_frequency:        frequency,
			alert_email:           alertEmail,
			slack_webhook_url:     slackUrl,
		};
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
						{ /* Vulnerability Advisor */ }
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

						{ /* Scan Schedule */ }
						<tr>
							<th scope="row">
								<label htmlFor="scan_frequency">
									{ __( 'Scan Frequency', 'wp-security' ) }
								</label>
							</th>
							<td>
								<select
									id="scan_frequency"
									name="scan_frequency"
									value={ frequency }
									onChange={ ( e ) => setFrequency( e.target.value ) }
								>
									<option value="hourly">{ __( 'Hourly', 'wp-security' ) }</option>
									<option value="daily">{ __( 'Daily', 'wp-security' ) }</option>
									<option value="weekly">{ __( 'Weekly', 'wp-security' ) }</option>
								</select>
								<p className="description">
									{ __( 'How often WP Security runs an automatic full scan.', 'wp-security' ) }
								</p>
							</td>
						</tr>

						{ /* Alert Email */ }
						<tr>
							<th scope="row">
								<label htmlFor="alert_email">
									{ __( 'Alert Email', 'wp-security' ) }
								</label>
							</th>
							<td>
								<input
									id="alert_email"
									name="alert_email"
									type="email"
									className="regular-text"
									value={ alertEmail }
									onChange={ ( e ) => setAlertEmail( e.target.value ) }
									placeholder={ __( 'admin@example.com', 'wp-security' ) }
								/>
								<p className="description">
									{ __( 'Receive an email when a new CRITICAL finding is discovered. Leave blank to disable.', 'wp-security' ) }
								</p>
							</td>
						</tr>

						{ /* Slack Webhook */ }
						<tr>
							<th scope="row">
								<label htmlFor="slack_webhook_url">
									{ __( 'Slack Webhook URL', 'wp-security' ) }
								</label>
							</th>
							<td>
								<input
									id="slack_webhook_url"
									name="slack_webhook_url"
									type="url"
									className="regular-text"
									value={ slackUrl }
									onChange={ ( e ) => setSlackUrl( e.target.value ) }
									placeholder="https://hooks.slack.com/services/…"
								/>
								<p className="description">
									{ __( 'Post a Slack message when a CRITICAL finding is discovered. Leave blank to disable.', 'wp-security' ) }
								</p>
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
