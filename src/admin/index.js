/* global GDB_SETTINGS */
( function ( wp ) {
	const { useEffect, useState } = wp.element;
	const { Button, Notice, TextControl, Spinner } = wp.components;
	const apiFetch = wp.apiFetch;

	apiFetch.use( wp.apiFetch.createNonceMiddleware( GDB_SETTINGS.nonce ) );

	const App = () => {
		const [ sets, setSets ] = useState( [] );
		const [ isLoading, setIsLoading ] = useState( true );
		const [ newSetName, setNewSetName ] = useState( '' );
		const [ notice, setNotice ] = useState( null );

		const loadSets = () => {
			setIsLoading( true );
			apiFetch( { path: GDB_SETTINGS.restPath + '/sets' } )
				.then( ( data ) => {
					setSets( data || [] );
				} )
				.catch( () => setSets( [] ) )
				.finally( () => setIsLoading( false ) );
		};

		useEffect( () => {
			loadSets();
		}, [] );

		const addSet = () => {
			const name = newSetName.trim() || 'Nieuwe set';
			setNotice( null );
			apiFetch( {
				path: GDB_SETTINGS.restPath + '/sets',
				method: 'POST',
				data: { name },
			} )
				.then( ( response ) => {
					setSets( [ ...sets, response ] );
					setNewSetName( '' );
				} )
				.catch( ( error ) => {
					const message = error && error.message ? error.message : 'Aanmaken mislukt.';
					setNotice( { status: 'error', message } );
				} );
		};

		const removeSet = ( setId ) => {
			if ( ! setId ) {
				return;
			}
			setNotice( null );
			apiFetch( {
				path: GDB_SETTINGS.restPath + '/sets/' + setId,
				method: 'DELETE',
			} )
				.then( () => {
					setSets( sets.filter( ( set ) => set.id !== setId ) );
				} )
				.catch( ( error ) => {
					const message = error && error.message ? error.message : 'Verwijderen mislukt.';
					setNotice( { status: 'error', message } );
				} );
		};

		const editSet = ( set ) => {
			if ( set && set.editUrl ) {
				window.location.href = set.editUrl;
				return;
			}
			const baseUrl = GDB_SETTINGS.editorUrl || '';
			const separator = baseUrl.indexOf( '?' ) === -1 ? '?' : '&';
			window.location.href = baseUrl + separator + 'gdb_set=' + encodeURIComponent( set.id );
		};

		return wp.element.createElement(
			'div',
			{ className: 'gdb-admin' },
			notice &&
				wp.element.createElement(
					Notice,
					{ status: notice.status, className: 'gdb-admin__notice', isDismissible: true, onRemove: () => setNotice( null ) },
					notice.message
				),
			wp.element.createElement(
				'div',
				{ className: 'gdb-admin__sets' },
				wp.element.createElement(
					'div',
					{ className: 'gdb-admin__set-create' },
					wp.element.createElement( TextControl, {
						label: 'Nieuwe set',
						value: newSetName,
						onChange: setNewSetName,
						placeholder: 'Naam...',
					} ),
					wp.element.createElement(
						Button,
						{ variant: 'secondary', onClick: addSet },
						'Set aanmaken'
					)
				),
				isLoading &&
					! sets.length &&
					wp.element.createElement(
						'div',
						{ className: 'gdb-admin__loading' },
						wp.element.createElement( Spinner, null ),
						wp.element.createElement( 'span', null, 'Sets laden...' )
					),
				! isLoading &&
					wp.element.createElement(
						'div',
						{ className: 'gdb-admin__table' },
						wp.element.createElement(
							'div',
							{ className: 'gdb-admin__table-header' },
							wp.element.createElement( 'div', null, 'Naam' ),
							wp.element.createElement( 'div', null, 'Items' ),
							wp.element.createElement( 'div', null, 'Acties' )
						),
						sets.map( ( set ) =>
							wp.element.createElement(
								'div',
								{ className: 'gdb-admin__table-row', key: set.id },
								wp.element.createElement( 'div', { className: 'gdb-admin__table-title' }, set.name ),
								wp.element.createElement( 'div', null, set.itemsCount || 0 ),
								wp.element.createElement(
									'div',
									{ className: 'gdb-admin__table-actions' },
									wp.element.createElement(
										Button,
										{ variant: 'secondary', onClick: () => editSet( set ) },
										'Bewerken'
									),
									wp.element.createElement(
										Button,
										{ variant: 'tertiary', isDestructive: true, onClick: () => removeSet( set.id ) },
										'Verwijderen'
									)
								)
							)
						)
					)
			)
		);
	};

	wp.element.render( wp.element.createElement( App ), document.getElementById( 'gdb-admin-app' ) );
} )( window.wp );
