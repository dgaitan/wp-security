import { useState, useEffect } from '@wordpress/element';
import { __ } from '@wordpress/i18n';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { saveMaintenanceNotes } from '../api/maintenanceReport';
import { Button } from './Button';

/**
 * Free-text fields (time spent, client notes, follow-up notes) an admin
 * attaches to a maintenance review — persisted to wpsec_maintenance_notes.
 *
 * @param {{ notes: object|null }} props
 */
export function MaintenanceNotesForm( { notes } ) {
	const queryClient = useQueryClient();
	const [ timeSpent, setTimeSpent ]     = useState( '' );
	const [ clientNotes, setClientNotes ] = useState( '' );
	const [ followUp, setFollowUp ]       = useState( '' );
	const [ saved, setSaved ]             = useState( false );

	useEffect( () => {
		if ( notes ) {
			setTimeSpent( notes.time_spent_minutes ?? '' );
			setClientNotes( notes.client_notes ?? '' );
			setFollowUp( notes.follow_up_notes ?? '' );
		}
	}, [ notes ] );

	const mutation = useMutation( {
		mutationFn: saveMaintenanceNotes,
		onSuccess:  () => {
			queryClient.invalidateQueries( { queryKey: [ 'maintenance-report' ] } );
			setSaved( true );
			setTimeout( () => setSaved( false ), 3000 );
		},
	} );

	function handleSubmit( e ) {
		e.preventDefault();
		mutation.mutate( {
			time_spent_minutes: timeSpent === '' ? undefined : Number( timeSpent ),
			client_notes:       clientNotes,
			follow_up_notes:    followUp,
		} );
	}

	return (
		<form className="wpsec-maintenance-notes-form" onSubmit={ handleSubmit }>
			{ saved && (
				<div className="notice notice-success is-dismissible">
					<p>{ __( 'Notes saved.', 'wp-security' ) }</p>
				</div>
			) }

			<p>
				<label htmlFor="wpsec-time-spent">
					{ __( 'Time spent (minutes)', 'wp-security' ) }
				</label>
				<br />
				<input
					id="wpsec-time-spent"
					type="number"
					min="0"
					className="small-text"
					value={ timeSpent }
					onChange={ ( e ) => setTimeSpent( e.target.value ) }
				/>
			</p>

			<p>
				<label htmlFor="wpsec-client-notes">
					{ __( 'Client notes', 'wp-security' ) }
				</label>
				<br />
				<textarea
					id="wpsec-client-notes"
					className="large-text"
					rows="4"
					value={ clientNotes }
					onChange={ ( e ) => setClientNotes( e.target.value ) }
				/>
			</p>

			<p>
				<label htmlFor="wpsec-follow-up-notes">
					{ __( 'Follow-up notes', 'wp-security' ) }
				</label>
				<br />
				<textarea
					id="wpsec-follow-up-notes"
					className="large-text"
					rows="4"
					value={ followUp }
					onChange={ ( e ) => setFollowUp( e.target.value ) }
				/>
			</p>

			<Button type="submit" disabled={ mutation.isPending }>
				{ mutation.isPending
					? __( 'Saving…', 'wp-security' )
					: __( 'Save Notes', 'wp-security' ) }
			</Button>
		</form>
	);
}
