( function () {
	'use strict';

	const fields = Array.from( document.querySelectorAll( '[data-quill-field]' ) );
	const managedRegions = Array.from( document.querySelectorAll( '[data-quill-managed]' ) );
	const toolbar = document.querySelector( '[data-quill-toolbar]' );
	const toggle = document.querySelector( '[data-quill-toggle]' );
	const status = document.querySelector( '[data-quill-status]' );
	const highlight = document.querySelector( '[data-quill-highlight]' );
	const highlightLabel = document.querySelector( '[data-quill-highlight-label]' );
	const managedEdit = document.querySelector( '[data-quill-managed-edit]' );
	const popup = document.querySelector( '[data-quill-popup]' );
	const popupLabel = document.querySelector( '[data-quill-popup-label]' );
	const form = document.querySelector( '[data-quill-form]' );
	const textEditor = document.querySelector( '[data-quill-text-editor]' );
	const input = document.querySelector( '[data-quill-input]' );
	const richtextEditor = document.querySelector( '[data-quill-richtext-editor]' );
	const richtextInput = document.querySelector( '[data-quill-richtext-input]' );
	const tools = Array.from( document.querySelectorAll( '[data-quill-command]' ) );
	const linkRow = document.querySelector( '[data-quill-link-row]' );
	const linkInput = document.querySelector( '[data-quill-link-input]' );
	const linkApply = document.querySelector( '[data-quill-link-apply]' );
	const buttonEditor = document.querySelector( '[data-quill-button-editor]' );
	const buttonText = document.querySelector( '[data-quill-button-text]' );
	const buttonUrl = document.querySelector( '[data-quill-button-url]' );
	const imageEditor = document.querySelector( '[data-quill-image-editor]' );
	const imagePreview = document.querySelector( '[data-quill-image-preview]' );
	const replaceImage = document.querySelector( '[data-quill-replace-image]' );
	const error = document.querySelector( '[data-quill-error]' );
	const cancel = document.querySelector( '[data-quill-cancel]' );
	const save = document.querySelector( '[data-quill-save]' );

	if (
		( ! fields.length && ! managedRegions.length ) ||
		! window.quillEditor ||
		! toolbar || ! toggle || ! highlight || ! highlightLabel || ! managedEdit || ! popup || ! form ||
		! textEditor || ! richtextEditor || ! richtextInput || ! linkRow || ! buttonEditor || ! imageEditor
	) {
		return;
	}

	const editors = {
		text: textEditor,
		richtext: richtextEditor,
		button: buttonEditor,
		image: imageEditor,
	};

	let active = false;
	let selectedField = null;
	let statusTimer = null;
	let pendingAttachmentId = 0;
	let lastRange = null;
	let linkRange = null;
	const labels = {
		openQuill: 'Open Quill',
		closeQuill: 'Close Quill',
		saved: 'Saved',
		error: 'Something went wrong. Please try again.',
		chooseImage: 'Choose image',
		useImage: 'Use image',
		selectText: 'Select the text you want to turn into a link first.',
		...( window.quillEditor.labels || {} ),
	};

	// Enter creates paragraphs, not divs, in the rich text editor.
	document.execCommand( 'defaultParagraphSeparator', false, 'p' );

	toolbar.hidden = false;

	const fieldType = ( field ) => ( editors[ field.dataset.quillType ] ? field.dataset.quillType : 'text' );

	// A button's editable text sits in its own span when the theme adds an icon.
	const buttonLabel = ( field ) => field.querySelector( '.quill-button__text' ) || field;

	/**
	 * Add a scheme to bare links, e.g. "example.com" or "hello@example.com".
	 */
	const normalizeUrl = ( value ) => {
		const url = value.trim();

		// Keep full URLs, mailto/tel/sms links, and relative links. "example.com:8080" is not a scheme.
		if ( ! url || /^([a-z][a-z0-9+.-]*:\/\/|(mailto|tel|sms):|\/|#|\?)/i.test( url ) ) {
			return url;
		}

		if ( /^[^\s@/]+@[^\s@/]+\.[^\s@/]+$/.test( url ) ) {
			return `mailto:${ url }`;
		}

		return `https://${ url }`;
	};

	const showSaved = () => {
		window.clearTimeout( statusTimer );
		status.textContent = labels.saved;
		status.hidden = false;
		statusTimer = window.setTimeout( () => {
			status.hidden = true;
		}, 1600 );
	};

	const showError = ( message ) => {
		error.textContent = message || labels.error;
		error.hidden = false;
	};

	const setHighlight = ( target ) => {
		if ( ! active || ! target ) {
			highlight.hidden = true;
			return;
		}

		const rect = target.getBoundingClientRect();
		const isManaged = target.hasAttribute( 'data-quill-managed' );
		const editUrl = isManaged ? target.dataset.quillManagedEditUrl : '';

		highlight.style.left = `${ Math.max( 0, rect.left - 5 ) }px`;
		highlight.style.top = `${ Math.max( 0, rect.top - 5 ) }px`;
		highlight.style.width = `${ rect.width + 10 }px`;
		highlight.style.height = `${ rect.height + 10 }px`;
		highlightLabel.textContent = isManaged ? target.dataset.quillManagedLabel : target.dataset.quillLabel;
		managedEdit.hidden = ! editUrl;
		managedEdit.href = editUrl || '#';
		managedEdit.textContent = editUrl ? target.dataset.quillManagedActionLabel : '';
		highlight.classList.toggle( 'quill-highlight--managed', isManaged );
		highlight.classList.toggle( 'quill-highlight--with-action', Boolean( editUrl ) );
		highlight.classList.toggle( 'quill-highlight--label-inside', rect.top < 40 );
		highlight.hidden = false;
	};

	const positionPopup = ( field ) => {
		const rect = field.getBoundingClientRect();
		const gap = 12;
		const edge = 16;
		const popupRect = popup.getBoundingClientRect();
		const left = Math.min(
			Math.max( rect.left, edge ),
			window.innerWidth - popupRect.width - edge
		);
		let top = rect.bottom + gap;

		if ( top + popupRect.height > window.innerHeight - edge ) {
			top = Math.max( edge, rect.top - popupRect.height - gap );
		}

		popup.style.right = 'auto';
		popup.style.bottom = 'auto';
		popup.style.left = `${ left }px`;
		popup.style.top = `${ top }px`;
	};

	const resizeInput = () => {
		const minimumHeight = 42;
		const maximumHeight = 220;

		input.style.height = 'auto';
		input.style.height = `${ Math.min( Math.max( input.scrollHeight, minimumHeight ), maximumHeight ) }px`;
		input.style.overflowY = input.scrollHeight > maximumHeight ? 'auto' : 'hidden';

		if ( selectedField && ! popup.hidden ) {
			positionPopup( selectedField );
		}
	};

	/*
	 * Rich text helpers.
	 */

	const selectRange = ( range ) => {
		const selection = window.getSelection();

		selection.removeAllRanges();
		selection.addRange( range );
	};

	const getEditorRange = () => {
		const selection = window.getSelection();

		if ( ! selection.rangeCount ) {
			return null;
		}

		const range = selection.getRangeAt( 0 );

		return richtextInput.contains( range.commonAncestorContainer ) ? range : null;
	};

	const getLinkAt = ( range ) => {
		if ( ! range ) {
			return null;
		}

		const node = range.startContainer;
		const element = Node.ELEMENT_NODE === node.nodeType ? node : node.parentElement;
		const link = element ? element.closest( 'a' ) : null;

		return link && richtextInput.contains( link ) ? link : null;
	};

	const focusEditor = () => {
		richtextInput.focus();

		if ( lastRange ) {
			selectRange( lastRange );
		}
	};

	const updateTools = () => {
		if ( richtextEditor.hidden ) {
			return;
		}

		tools.forEach( ( tool ) => {
			const command = tool.dataset.quillCommand;
			const pressed = 'link' === command
				? Boolean( getLinkAt( lastRange ) )
				: document.queryCommandState( command );

			tool.setAttribute( 'aria-pressed', pressed ? 'true' : 'false' );
		} );
	};

	const hideLinkRow = () => {
		linkRow.hidden = true;
		linkInput.value = '';
		linkRange = null;
	};

	const toggleLinkRow = () => {
		if ( ! linkRow.hidden ) {
			hideLinkRow();
			focusEditor();
			return;
		}

		const range = getEditorRange() || lastRange;
		const link = getLinkAt( range );

		if ( ! range || ( range.collapsed && ! link ) ) {
			showError( labels.selectText );
			return;
		}

		error.hidden = true;

		if ( link ) {
			linkRange = document.createRange();
			linkRange.selectNodeContents( link );
		} else {
			linkRange = range.cloneRange();
		}

		linkInput.value = link ? link.getAttribute( 'href' ) || '' : '';
		linkRow.hidden = false;
		positionPopup( selectedField );
		linkInput.focus();
		linkInput.select();
	};

	const applyLink = () => {
		const url = normalizeUrl( linkInput.value );
		const range = linkRange;

		hideLinkRow();
		richtextInput.focus();

		if ( range ) {
			selectRange( range );
		}

		// An empty address removes the link.
		document.execCommand( url ? 'createLink' : 'unlink', false, url || null );
		updateTools();
	};

	const placeCaretAtEnd = ( element ) => {
		const range = document.createRange();

		range.selectNodeContents( element );
		range.collapse( false );
		selectRange( range );
	};

	const replaceTag = ( element, tagName ) => {
		const replacement = element.ownerDocument.createElement( tagName );

		replacement.append( ...element.childNodes );
		element.replaceWith( replacement );
	};

	/**
	 * Turn browser editing markup into the tags Quill stores. The server sanitizes again.
	 */
	const cleanRichtext = ( html ) => {
		// An inert document, so nothing in the markup loads or runs.
		const container = document.implementation.createHTMLDocument( '' ).createElement( 'div' );

		// Parsing the serialized HTML also moves lists that Chrome nests inside <p> out of it.
		container.innerHTML = html;
		container.querySelectorAll( 'div' ).forEach( ( element ) => replaceTag( element, 'p' ) );
		container.querySelectorAll( 'b' ).forEach( ( element ) => replaceTag( element, 'strong' ) );
		container.querySelectorAll( 'i' ).forEach( ( element ) => replaceTag( element, 'em' ) );
		container.querySelectorAll( 'p' ).forEach( ( element ) => {
			if ( ! element.textContent.trim() ) {
				element.remove();
			}
		} );

		return container.innerHTML.trim();
	};

	/*
	 * Popup.
	 */

	const closePopup = () => {
		popup.hidden = true;
		popup.classList.remove( 'quill-popup--wide' );
		selectedField = null;
		toggle.disabled = false;
		error.hidden = true;
		error.textContent = '';
		input.style.height = '';
		richtextInput.innerHTML = '';
		pendingAttachmentId = 0;
		lastRange = null;
		hideLinkRow();
	};

	const openPopup = ( field ) => {
		const type = fieldType( field );

		selectedField = field;
		popupLabel.textContent = field.dataset.quillLabel;
		Object.keys( editors ).forEach( ( key ) => {
			editors[ key ].hidden = key !== type;
		} );
		popup.classList.toggle( 'quill-popup--wide', 'richtext' === type );
		hideLinkRow();
		error.hidden = true;
		error.textContent = '';
		popup.hidden = false;
		toggle.disabled = true;
		setHighlight( field );

		if ( 'image' === type ) {
			pendingAttachmentId = Number.parseInt( field.dataset.quillAttachmentId, 10 ) || 0;
			imagePreview.src = field.currentSrc || field.src;
			imagePreview.alt = field.alt || '';
			positionPopup( field );
			window.requestAnimationFrame( () => replaceImage.focus() );
			return;
		}

		if ( 'richtext' === type ) {
			lastRange = null;
			richtextInput.innerHTML = field.innerHTML;
			positionPopup( field );
			window.requestAnimationFrame( () => {
				richtextInput.focus();
				placeCaretAtEnd( richtextInput );
				updateTools();
			} );
			return;
		}

		if ( 'button' === type ) {
			const href = field.getAttribute( 'href' ) || '';

			buttonText.value = buttonLabel( field ).textContent.trim();
			buttonText.maxLength = Number.parseInt( field.dataset.quillMaxLength, 10 ) || 40;
			buttonUrl.value = '#' === href ? '' : href;
			positionPopup( field );
			window.requestAnimationFrame( () => {
				buttonText.focus();
				buttonText.select();
			} );
			return;
		}

		input.value = field.textContent.trim();
		input.maxLength = Number.parseInt( field.dataset.quillMaxLength, 10 ) || 160;
		resizeInput();
		window.requestAnimationFrame( () => {
			input.focus();
			input.select();
		} );
	};

	const setActive = ( nextActive ) => {
		active = nextActive;
		document.documentElement.classList.toggle( 'quill-selection-active', active );
		toggle.setAttribute( 'aria-pressed', active ? 'true' : 'false' );
		( toggle.querySelector( '[data-quill-toggle-label]' ) || toggle ).textContent = active ? labels.closeQuill : labels.openQuill;

		fields.forEach( ( field ) => {
			field.tabIndex = active ? 0 : -1;
		} );

		if ( ! active ) {
			closePopup();
			highlight.hidden = true;
			status.hidden = true;
		}
	};

	const getSubmittedValue = ( type ) => {
		switch ( type ) {
			case 'image':
				return pendingAttachmentId;

			case 'richtext':
				return cleanRichtext( richtextInput.innerHTML );

			case 'button':
				return {
					text: buttonText.value,
					url: normalizeUrl( buttonUrl.value ),
				};

			default:
				return input.value;
		}
	};

	const updateField = ( field, data ) => {
		switch ( data.type ) {
			case 'image':
				if ( ! data.image ) {
					return;
				}

				field.dataset.quillAttachmentId = String( data.image.id );
				field.src = data.image.url;
				field.alt = data.image.alt;
				field.width = data.image.width;
				field.height = data.image.height;

				if ( data.image.srcset ) {
					field.srcset = data.image.srcset;
				} else {
					field.removeAttribute( 'srcset' );
				}

				if ( data.image.sizes ) {
					field.sizes = data.image.sizes;
				} else {
					field.removeAttribute( 'sizes' );
				}
				return;

			case 'richtext':
				// Sanitized by the server before it is returned.
				field.innerHTML = data.value;
				return;

			case 'button':
				buttonLabel( field ).textContent = data.value.text;
				field.setAttribute( 'href', data.value.url );
				return;

			default:
				field.textContent = data.value;
		}
	};

	const saveField = async () => {
		if ( ! selectedField || save.disabled ) {
			return;
		}

		const savedFieldId = selectedField.dataset.quillField;

		save.disabled = true;
		cancel.disabled = true;
		error.hidden = true;

		try {
			const response = await window.fetch(
				`${ window.quillEditor.restUrl }${ encodeURIComponent( savedFieldId ) }`,
				{
					method: 'POST',
					credentials: 'same-origin',
					headers: {
						'Content-Type': 'application/json',
						'X-WP-Nonce': window.quillEditor.nonce,
					},
					body: JSON.stringify( {
						value: getSubmittedValue( fieldType( selectedField ) ),
						page_id: Number.parseInt( window.quillEditor.pageId, 10 ) || 0,
					} ),
				}
			);
			let data = null;

			try {
				data = await response.json();
			} catch ( parseError ) {
				// A PHP error or proxy page instead of JSON.
				data = null;
			}

			if ( ! response.ok || ! data ) {
				throw new Error( data && data.message ? data.message : labels.error );
			}

			fields
				.filter( ( field ) => field.dataset.quillField === savedFieldId )
				.forEach( ( field ) => updateField( field, data ) );

			const savedField = selectedField;
			closePopup();
			setHighlight( savedField );
			showSaved();
		} catch ( saveError ) {
			showError( saveError.message );
		} finally {
			save.disabled = false;
			cancel.disabled = false;
		}
	};

	/*
	 * Events.
	 */

	toggle.addEventListener( 'click', () => {
		if ( toggle.disabled ) {
			return;
		}

		setActive( ! active );
	} );

	document.addEventListener( 'pointerover', ( event ) => {
		const field = event.target.closest( '[data-quill-field], [data-quill-managed]' );

		if ( active && ! selectedField && field ) {
			setHighlight( field );
		}
	} );

	document.addEventListener( 'pointerout', ( event ) => {
		const field = event.target.closest( '[data-quill-field], [data-quill-managed]' );

		if (
			active &&
			! selectedField &&
			field &&
			! field.contains( event.relatedTarget ) &&
			! highlight.contains( event.relatedTarget )
		) {
			highlight.hidden = true;
		}
	} );

	document.addEventListener(
		'click',
		( event ) => {
			const field = event.target.closest( '[data-quill-field]' );
			const managedRegion = event.target.closest( '[data-quill-managed]' );

			if ( ! active || selectedField ) {
				return;
			}

			if ( managedRegion && ! field ) {
				event.preventDefault();
				event.stopPropagation();
				setHighlight( managedRegion );
				return;
			}

			if ( ! field ) {
				return;
			}

			// Also stops button and rich text links from navigating while editing.
			event.preventDefault();
			event.stopPropagation();
			openPopup( field );
		},
		true
	);

	fields.forEach( ( field ) => {
		field.addEventListener( 'focus', () => {
			if ( active && ! selectedField ) {
				setHighlight( field );
			}
		} );

		field.addEventListener( 'keydown', ( event ) => {
			if ( active && 'Enter' === event.key && ! selectedField ) {
				event.preventDefault();
				openPopup( field );
			}
		} );
	} );

	form.addEventListener( 'submit', ( event ) => {
		event.preventDefault();
		saveField();
	} );

	cancel.addEventListener( 'click', closePopup );

	tools.forEach( ( tool ) => {
		// Keep the text selection in the editor when a tool is clicked.
		tool.addEventListener( 'mousedown', ( event ) => event.preventDefault() );

		tool.addEventListener( 'click', () => {
			const command = tool.dataset.quillCommand;

			if ( 'link' === command ) {
				toggleLinkRow();
				return;
			}

			focusEditor();
			document.execCommand( command, false, null );
			updateTools();
		} );
	} );

	document.addEventListener( 'selectionchange', () => {
		const range = getEditorRange();

		if ( range ) {
			lastRange = range.cloneRange();
		}

		updateTools();
	} );

	richtextInput.addEventListener( 'paste', ( event ) => {
		// Paste as plain text so formatting from Word or other sites is not carried over.
		event.preventDefault();
		document.execCommand( 'insertText', false, event.clipboardData.getData( 'text/plain' ) );
	} );

	richtextInput.addEventListener( 'input', () => {
		if ( selectedField ) {
			positionPopup( selectedField );
		}
	} );

	richtextInput.addEventListener( 'keydown', ( event ) => {
		const modifier = event.metaKey || event.ctrlKey;

		if ( modifier && 'Enter' === event.key ) {
			event.preventDefault();
			saveField();
		} else if ( modifier && 'k' === event.key.toLowerCase() ) {
			event.preventDefault();
			toggleLinkRow();
		}
	} );

	linkApply.addEventListener( 'click', applyLink );

	linkInput.addEventListener( 'keydown', ( event ) => {
		if ( 'Enter' === event.key && ! event.isComposing ) {
			// Apply the link instead of submitting and saving the whole field.
			event.preventDefault();
			applyLink();
		}
	} );

	replaceImage.addEventListener( 'click', () => {
		if ( ! window.wp || ! window.wp.media ) {
			showError( labels.error );
			return;
		}

		const mediaFrame = window.wp.media( {
			title: labels.chooseImage,
			button: {
				text: labels.useImage,
			},
			library: {
				type: 'image',
			},
			multiple: false,
		} );

		mediaFrame.state( 'library' ).set( 'content', 'browse' );

		if ( pendingAttachmentId ) {
			const currentAttachment = window.wp.media.attachment( pendingAttachmentId );

			currentAttachment.fetch();
			mediaFrame.state( 'library' ).get( 'selection' ).add( currentAttachment );
		}

		mediaFrame.on( 'open', () => {
			document.documentElement.classList.add( 'quill-media-open' );
			mediaFrame.content.mode( 'browse' );
			window.requestAnimationFrame( () => {
				const modalContainer = document.querySelector( '.media-modal .media-frame' );

				if ( modalContainer && modalContainer.getBoundingClientRect().height <= 0 ) {
					console.warn( 'Quill: the media modal is collapsed, probably because theme CSS targets a WordPress UI class such as .media-frame.' );
				}
			} );
		} );

		mediaFrame.on( 'close', () => {
			document.documentElement.classList.remove( 'quill-media-open' );

			if ( selectedField ) {
				setHighlight( selectedField );
				positionPopup( selectedField );
			}
		} );

		mediaFrame.on( 'select', () => {
			const attachment = mediaFrame.state().get( 'selection' ).first().toJSON();
			const preview = attachment.sizes && attachment.sizes.medium
				? attachment.sizes.medium.url
				: attachment.url;

			pendingAttachmentId = Number.parseInt( attachment.id, 10 );
			imagePreview.src = preview;
			imagePreview.alt = attachment.alt || '';
			error.hidden = true;
			positionPopup( selectedField );
		} );

		mediaFrame.open();
	} );

	input.addEventListener( 'input', resizeInput );

	input.addEventListener( 'keydown', ( event ) => {
		if ( 'Enter' === event.key && ! event.isComposing && 'text' === selectedField?.dataset.quillType ) {
			event.preventDefault();
			saveField();
		}
	} );

	document.addEventListener( 'keydown', ( event ) => {
		if ( 'Escape' !== event.key ) {
			return;
		}

		if ( ! linkRow.hidden ) {
			hideLinkRow();
			focusEditor();
		} else if ( selectedField ) {
			closePopup();
		} else if ( active ) {
			setActive( false );
			toggle.focus();
		}
	} );

	window.addEventListener( 'resize', () => {
		if ( selectedField ) {
			setHighlight( selectedField );
			positionPopup( selectedField );
		}
	} );

	window.addEventListener(
		'scroll',
		( event ) => {
			// Scrolling inside the popup's editor must not move the popup.
			if ( popup.contains( event.target ) ) {
				return;
			}

			if ( selectedField ) {
				setHighlight( selectedField );
				positionPopup( selectedField );
			} else {
				highlight.hidden = true;
			}
		},
		true
	);

	const requestParams = new URLSearchParams( window.location.search );

	if ( '1' === requestParams.get( 'quill-edit' ) ) {
		setActive( true );

		const requestedFieldId = requestParams.get( 'quill-field' );
		const requestedField = requestedFieldId
			? fields.find( ( field ) => field.dataset.quillField === requestedFieldId )
			: null;

		if ( requestedField ) {
			window.requestAnimationFrame( () => {
				requestedField.scrollIntoView( {
					behavior: 'smooth',
					block: 'center',
				} );

				window.setTimeout( () => openPopup( requestedField ), 350 );
			} );
		}
	}
}() );
