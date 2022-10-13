/**
 * WordPress dependencies
 */
import { __ } from '@wordpress/i18n';
import { useEffect, useRef, useState } from '@wordpress/element';

/**
 * Internal dependencies
 */
import Handle from './handle';
import ScreenShot from '../../screenshot-preview/src/block';

/**
 * Module constants
 */
const CARD_WIDTH = 100;
const CARD_GAP = 12;
/**
 * The default number of tiles that are advanced on next arrow click.
 */
const SET_WIDTH = CARD_WIDTH * 3;

/**
 * Properties of the ScreenShot object.
 *
 * @typedef {{link: string, previewLink: string, caption: string, title: string}} ScreenShot
 */

/**
 *
 * @param {Object}       props
 * @param {ScreenShot[]} props.items List of ScreenShot objects.
 * @param {string}       props.title Text to be displayed as the title of the slider.
 *
 * @return {Object} React element
 */
function Block( { items, title } ) {
	const outerRef = useRef();
	const [ scrollLeftPos, setScrollLeftPos ] = useState( 0 );
	const [ canPrevious, setCanPrevious ] = useState( false );
	const [ canNext, setCanNext ] = useState( true );

	// Calculate to total width of the content
	const totalContainerWidth = items.length * ( CARD_WIDTH + CARD_GAP ) - CARD_GAP;

	const scrollContainer = ( pos ) => {
		outerRef.current.scrollTo( {
			left: pos,
			behavior: 'smooth',
		} );
	};

	const handlePrev = () => {
		if ( ! canPrevious ) {
			return;
		}
		setScrollLeftPos( outerRef.current.scrollLeft - SET_WIDTH );
	};

	const handleNext = () => {
		if ( ! canNext ) {
			return;
		}
		setScrollLeftPos( outerRef.current.scrollLeft + SET_WIDTH );
	};

	useEffect( () => {
		scrollContainer( scrollLeftPos );
	}, [ scrollLeftPos ] );

	useEffect( () => {
		if ( ! outerRef.current ) {
			return;
		}

		const handleScrollEvent = () => {
			setCanPrevious( outerRef.current.scrollLeft > 0 );
			setCanNext( totalContainerWidth - outerRef.current.scrollLeft > outerRef.current.offsetWidth );
		};

		handleScrollEvent();

		outerRef.current.addEventListener( 'scroll', handleScrollEvent );

		return () => {
			outerRef.current.removeEventListener( 'scroll', handleScrollEvent );
		};
	}, [ outerRef ] );

	// Taken from @wordpress/edit-site proportions
	const initialWidth = 118;
	const initialHeight = 74;
	const aspectRatio = initialHeight / initialWidth;
	const width = 100;
	const height = width * aspectRatio;

	return (
		<div>
			<div className="horizontal-slider-header">
				<span>
					<h3 className="horizontal-slider-title">{ title }</h3>
				</span>
				{ ( canNext || canPrevious ) && (
					<span className="horizontal-slider-controls">
						<Handle
							text={ __( 'Previous style variations', 'wporg' ) }
							disabled={ ! canPrevious }
							onClick={ handlePrev }
						/>
						<Handle
							text={ __( 'Next style variations', 'wporg' ) }
							disabled={ ! canNext }
							onClick={ handleNext }
						/>
					</span>
				) }
			</div>
			<div className="horizontal-slider-wrapper" ref={ outerRef }>
				{ items.map( ( item ) => (
					<ScreenShot
						key={ item.title }
						{ ...item }
						width={ `${ width }px` }
						height={ `${ height }px` }
						aspectRatio={ aspectRatio }
						queryString={ `?vpw=${ initialWidth * 10 }&vph=${ initialHeight * 10 }` }
						isReady={ true }
					/>
				) ) }
			</div>
		</div>
	);
}

export default Block;