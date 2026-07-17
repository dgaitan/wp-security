import { useState, useEffect } from '@wordpress/element';
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import { fetchSettings, updateSettings } from '../api/settings';

/** Renders a {label,url} list as one "Label | URL" line per entry. */
function listToText( list ) {
	return ( list ?? [] ).map( ( item ) => `${ item.label ?? '' } | ${ item.url ?? '' }` ).join( '\n' );
}

/** Parses "Label | URL" lines back into a {label,url} list; blank lines skipped. */
function textToList( text ) {
	return text
		.split( '\n' )
		.map( ( line ) => line.trim() )
		.filter( Boolean )
		.map( ( line ) => {
			const [ label, url ] = line.split( '|' ).map( ( part ) => part?.trim() ?? '' );
			return { label: url ? label : '', url: url || label };
		} );
}

export function Settings() {
	const queryClient = useQueryClient();
	const [ saved, setSaved ]               = useState( false );
	const [ provider, setProvider ]         = useState( 'wpvulnerability' );
	const [ apiKey, setApiKey ]             = useState( '' );
	const [ frequency, setFrequency ]       = useState( 'daily' );
	const [ alertEmail, setAlertEmail ]     = useState( '' );
	const [ slackUrl, setSlackUrl ]         = useState( '' );
	const [ ctaUrls, setCtaUrls ]                 = useState( '' );
	const [ landingPages, setLandingPages ]       = useState( '' );
	const [ searchUrl, setSearchUrl ]             = useState( '' );
	const [ expectGtm, setExpectGtm ]             = useState( false );
	const [ expectGa4, setExpectGa4 ]             = useState( false );
	const [ expectMetaPixel, setExpectMetaPixel ] = useState( false );
	const [ cookieSignature, setCookieSignature ] = useState( '' );

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
			setCtaUrls( listToText( settings.cta_urls ) );
			setLandingPages( listToText( settings.key_landing_pages ) );
			setSearchUrl( settings.search_url ?? '' );
			setExpectGtm( Boolean( settings.expect_gtm ) );
			setExpectGa4( Boolean( settings.expect_ga4 ) );
			setExpectMetaPixel( Boolean( settings.expect_meta_pixel ) );
			setCookieSignature( settings.cookie_consent_custom_signature ?? '' );
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
			cta_urls:              textToList( ctaUrls ),
			key_landing_pages:     textToList( landingPages ),
			search_url:            searchUrl,
			expect_gtm:            expectGtm,
			expect_ga4:            expectGa4,
			expect_meta_pixel:     expectMetaPixel,
			cookie_consent_custom_signature: cookieSignature,
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

						{ /* Primary CTAs */ }
						<tr>
							<th scope="row">
								<label htmlFor="cta_urls">
									{ __( 'Primary CTAs', 'wp-security' ) }
								</label>
							</th>
							<td>
								<textarea
									id="cta_urls"
									className="large-text"
									rows="3"
									value={ ctaUrls }
									onChange={ ( e ) => setCtaUrls( e.target.value ) }
									placeholder="Get a Quote | https://example.com/quote"
								/>
								<p className="description">
									{ __( 'One per line: Label | URL. Checked by the Functional QA module\'s "Primary CTAs" check.', 'wp-security' ) }
								</p>
							</td>
						</tr>

						{ /* Key landing pages */ }
						<tr>
							<th scope="row">
								<label htmlFor="key_landing_pages">
									{ __( 'Key Landing Pages', 'wp-security' ) }
								</label>
							</th>
							<td>
								<textarea
									id="key_landing_pages"
									className="large-text"
									rows="3"
									value={ landingPages }
									onChange={ ( e ) => setLandingPages( e.target.value ) }
									placeholder="Pricing | https://example.com/pricing"
								/>
								<p className="description">
									{ __( 'One per line: Label | URL. Checked by the Functional QA module\'s "Key Landing Pages" check.', 'wp-security' ) }
								</p>
							</td>
						</tr>

						{ /* Search URL override */ }
						<tr>
							<th scope="row">
								<label htmlFor="search_url">
									{ __( 'Search URL Override', 'wp-security' ) }
								</label>
							</th>
							<td>
								<input
									id="search_url"
									type="url"
									className="regular-text"
									value={ searchUrl }
									onChange={ ( e ) => setSearchUrl( e.target.value ) }
									placeholder="https://example.com/?s=test"
								/>
								<p className="description">
									{ __( 'Leave blank to use the default WordPress search URL.', 'wp-security' ) }
								</p>
							</td>
						</tr>

						{ /* Expected marketing/analytics tags */ }
						<tr>
							<th scope="row">
								<label>{ __( 'Expected Tracking Tools', 'wp-security' ) }</label>
							</th>
							<td>
								<fieldset>
									<legend className="screen-reader-text">
										{ __( 'Expected Tracking Tools', 'wp-security' ) }
									</legend>
									<label>
										<input
											type="checkbox"
											checked={ expectGtm }
											onChange={ ( e ) => setExpectGtm( e.target.checked ) }
										/>
										{ ' ' }
										{ __( 'Google Tag Manager', 'wp-security' ) }
									</label>
									<br />
									<label>
										<input
											type="checkbox"
											checked={ expectGa4 }
											onChange={ ( e ) => setExpectGa4( e.target.checked ) }
										/>
										{ ' ' }
										{ __( 'Google Analytics 4', 'wp-security' ) }
									</label>
									<br />
									<label>
										<input
											type="checkbox"
											checked={ expectMetaPixel }
											onChange={ ( e ) => setExpectMetaPixel( e.target.checked ) }
										/>
										{ ' ' }
										{ __( 'Meta Pixel', 'wp-security' ) }
									</label>
								</fieldset>
								<p className="description">
									{ __( 'Absence only warns for tools checked here — unchecked tools are informational only.', 'wp-security' ) }
								</p>
							</td>
						</tr>

						{ /* Cookie consent custom signature */ }
						<tr>
							<th scope="row">
								<label htmlFor="cookie_consent_custom_signature">
									{ __( 'Custom Cookie Consent Signature', 'wp-security' ) }
								</label>
							</th>
							<td>
								<input
									id="cookie_consent_custom_signature"
									type="text"
									className="regular-text"
									value={ cookieSignature }
									onChange={ ( e ) => setCookieSignature( e.target.value ) }
									placeholder="my-consent-script.js"
								/>
								<p className="description">
									{ __( 'A distinctive string (script filename, variable name) to detect a consent platform not in the built-in list.', 'wp-security' ) }
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
