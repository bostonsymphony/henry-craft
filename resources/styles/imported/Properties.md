Properties
==========

## Root Properties

The following custom properties are provided by Pronto. These are set by changing the values in config/.

### `--root-font-family`, `--root-font-family-heading`
The default font families for the body and headings. We recommend using font stacks from https://modernfontstacks.com/

### `--root-font-size`
Default font size.

### `--root-color`
Default text color.

### `--root-color-inactive`
Default inactive text color.

### `--root-border-color`, `--root-border-width`
Default border color and width, primarily for form inputs.

### `--root-background-color`
Default background color.

### `--root-background-color-inactive`
Default background color for inactive elements, primarily for form inputs.

### `--root-box-shadow-low`, `--root-box-shadow-med`, `--root-box-shadow-high`
Three different starting shadows for different implied heights.

### `--root-breakpoint-sm`, `--root-breakpoint-md`, `--root-breakpoint-lg`
The three breakpoints in pixels **without units**. Leaving off the units makes these much more flexible. Also note, these variables cannot be used in media queries - it's a limitation of CSS.

### `--root-gap`
The default gap between elements.

### `--root-padding-block`, `--root-padding-inline`
The default block and inline padding for elements.


## `--accent-color`

`--accent-color` is meant to be overridden, and represent the accent color for a component, similar to the `accent-color` CSS property. Pronto components will try to use this color, and expect that the contrast between this color and white to be sufficient for accessibility.

It works with the color utilities (imported/utilities/colors) to allow all Pronto components to be re-colorable with the color utility classes. It's recommended that, instead of component-specific modifiers for changing the color of components, that you use `--accent-color` and define as necessary additional color utility classes.
