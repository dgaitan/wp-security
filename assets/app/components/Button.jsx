import { cn } from '../utils/cn';

/** @type {Record<string, string>} */
const BUTTON_VARIANTS = {
    primary: 'button button-primary',
    secondary: 'button button-secondary',
    tertiary: 'button button-tertiary',
};

/** @type {Record<string, string>} */
const BUTTON_SIZES = {
    small: 'button-small',
    medium: 'button-medium',
    large: 'button-large',
};


/**
 * A button component with a variety of types and styles.
 *
 * @param {Object} props - The props for the button.
 * @param {string} props.type - The type of button to render.
 * @param {string} props.className - The class name to apply to the button.
 * @param {React.ReactNode} props.children - The content to render inside the button.
 */
export function Button( { children, variant = 'primary', size = 'medium', ...props } ) {
    const buttonType = BUTTON_VARIANTS[variant] || BUTTON_VARIANTS.primary;
    const buttonSize = BUTTON_SIZES[size] || BUTTON_SIZES.medium;
    const className = cn( buttonType, buttonSize, props.className );
	return (
		<button { ...props } className={ className }>
			{ children }
		</button>
	);
}