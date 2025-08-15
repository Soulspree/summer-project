# Style Guide

This project uses a simple design system to ensure consistency across components and pages.

## Design Tokens

| Token | Description | Example |
|-------|-------------|---------|
| `--color-primary` | Primary brand color used for buttons and highlights. | `#0057B8` |
| `--color-secondary` | Secondary accent color for backgrounds or borders. | `#FFB81C` |
| `--font-base` | Base font family for body text. | `"Helvetica Neue", Arial, sans-serif` |
| `--spacing-unit` | Base spacing unit for margins and padding. | `8px` |

These tokens can be defined in CSS as custom properties and reused throughout the application for consistency.

## Component States

Components should provide clear visual feedback for different interactions:

- **Default** – initial appearance.
- **Hover** – slightly darkened background or underline to indicate interactivity.
- **Focus** – visible outline to aid keyboard navigation.
- **Active** – pressed state with increased contrast.
- **Disabled** – reduced opacity and no pointer events.

## Breakpoints

Responsive breakpoints align with common device sizes:

| Breakpoint | Min Width | Common Usage |
|------------|-----------|--------------|
| `sm` | 576px | Small devices (portrait phones) |
| `md` | 768px | Tablets |
| `lg` | 992px | Desktops |
| `xl` | 1200px | Large desktops |

Components and layouts should adapt at these breakpoints to maintain usability across devices.

## Accessibility & Testing

- Accessibility checks can be run with Lighthouse (`npx lighthouse <url> --only-categories=accessibility`) and Axe (`npx @axe-core/cli <url>`).
- Manual testing of booking, dashboard, and search flows helps ensure no regressions after visual updates. 
