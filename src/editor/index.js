/* global GDB_EDITOR */
( function ( wp ) {
	if ( ! wp || ! wp.editPost || ! wp.plugins || ! wp.plugins.registerPlugin ) {
		return;
	}

	const { useEffect, useState } = wp.element;
	const { PluginSidebar, PluginSidebarMoreMenuItem } = wp.editPost;
	const { Button, PanelBody } = wp.components;
	const apiFetch = wp.apiFetch;
	const BlockPreview = wp.blockEditor ? wp.blockEditor.BlockPreview : null;

	apiFetch.use( wp.apiFetch.createNonceMiddleware( GDB_EDITOR.nonce ) );

	const Plugin = () => {
		const [ sets, setSets ] = useState( [] );
		const postType =
			GDB_EDITOR.postType ||
			( wp.data && wp.data.select( 'core/editor' )
				? wp.data.select( 'core/editor' ).getCurrentPostType()
				: '' );

		const isSetEditor = postType === 'gdb_set';

		useEffect( () => {
			apiFetch( { path: GDB_EDITOR.restPath + '/sets' } ).then( ( data ) => {
				setSets( data || [] );
			} );
		}, [] );

		useEffect( () => {
			const updatePreviews = () => {
				const previews = document.querySelectorAll( '.gdb-set-card__preview' );
				previews.forEach( ( preview ) => {
					const content = preview.querySelector( '.block-editor-block-preview__content' );
					if ( ! content ) {
						return;
					}

					const containerWidth = preview.clientWidth;
					const containerHeight = preview.clientHeight;
					const contentWidth = content.scrollWidth || content.offsetWidth || containerWidth;
					const contentHeight = content.scrollHeight || content.offsetHeight || containerHeight;
					if ( ! containerWidth || ! containerHeight ) {
						return;
					}

					let scale = containerWidth / contentWidth;
					scale = Math.max( 0.15, Math.min( 1, scale ) );

					const scrollDistance = Math.max( 0, contentHeight * scale - containerHeight );
					preview.style.setProperty( '--gdb-preview-scale', scale.toString() );
					preview.style.setProperty( '--gdb-preview-scroll', `${ scrollDistance }px` );
					preview.classList.add( 'is-ready' );
					if ( scrollDistance > 1 ) {
						preview.classList.add( 'is-scrollable' );
					} else {
						preview.classList.remove( 'is-scrollable' );
					}
				} );
			};

			const raf = requestAnimationFrame( updatePreviews );
			const timeout = setTimeout( updatePreviews, 300 );
			const interval = setInterval( updatePreviews, 1000 );
			window.addEventListener( 'resize', updatePreviews );
			return () => {
				cancelAnimationFrame( raf );
				clearTimeout( timeout );
				clearInterval( interval );
				window.removeEventListener( 'resize', updatePreviews );
			};
		}, [ sets ] );

		const insertSet = ( set ) => {
			if ( ! set ) {
				return;
			}

			let blocks = [];
			if ( set.content ) {
				blocks = wp.blocks.parse( set.content );
			} else if ( set.items && set.items.length ) {
				set.items.forEach( ( item ) => {
					if ( item && item.content ) {
						blocks = blocks.concat( wp.blocks.parse( item.content ) );
					}
				} );
			}

			if ( ! blocks.length ) {
				return;
			}

			wp.data.dispatch( 'core/block-editor' ).insertBlocks( blocks );
		};

		const renderPreview = ( set ) => {
			if ( ! BlockPreview || ! set || ! set.content ) {
				return null;
			}
			const blocks = wp.blocks.parse( set.content );
			if ( ! blocks.length ) {
				return null;
			}
			return wp.element.createElement(
				'div',
				{ className: 'gdb-set-card__preview-inner' },
				wp.element.createElement( BlockPreview, { blocks } )
			);
		};

		if ( isSetEditor ) {
			return null;
		}

		return wp.element.createElement(
			wp.element.Fragment,
			null,
			wp.element.createElement(
				PluginSidebarMoreMenuItem,
				{ target: 'gdb-sets-sidebar' },
				'Gutenberg Sets'
			),
			wp.element.createElement(
				PluginSidebar,
				{
					name: 'gdb-sets-sidebar',
					title: 'Gutenberg Sets',
				},
				wp.element.createElement(
					'div',
					{ className: 'gdb-sets-panel' },
					wp.element.createElement(
						'div',
						{ className: 'gdb-set-list' },
						sets.map( ( set ) =>
							wp.element.createElement(
								'div',
								{
									className: 'gdb-set-card',
									key: set.id,
								},
								wp.element.createElement(
									'div',
									{ className: 'gdb-set-card__preview' },
									renderPreview( set )
								),
								wp.element.createElement(
									'div',
									{ className: 'gdb-set-card__title' },
									set.name
								),
								wp.element.createElement(
									'div',
									{ className: 'gdb-set-card__button' },
									wp.element.createElement(
										Button,
										{ variant: 'primary', onClick: () => insertSet( set ) },
										'Add to page'
									)
								)
							)
						)
					)
				)
			)
		);
	};

	wp.plugins.registerPlugin( 'gdb-default-blocks', { render: Plugin } );
} )( window.wp );
