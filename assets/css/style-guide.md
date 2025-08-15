# Style Guide

This project uses a simple design system built on CSS custom properties.

## Design Tokens

All tokens are defined in `custom.css` under the `:root` selector.

- **Colors**
  - `--color-primary`: #4f46e5
  - `--color-secondary`: #ec4899
  - `--color-surface`: #ffffff
  - `--color-background`: #f9fafb
  - `--color-text`: #1f2937
  - `--color-muted`: #6b7280
- **Fonts**
  - Headings: `Poppins`
  - Body: `Roboto`
- **Spacing** (scale based on multiples of 0.25rem)
  - `--spacing-xs`: 0.25rem
  - `--spacing-sm`: 0.5rem
  - `--spacing-md`: 1rem
  - `--spacing-lg`: 1.5rem
  - `--spacing-xl`: 2rem
- **Breakpoints**
  - `--breakpoint-sm`: 576px
  - `--breakpoint-md`: 768px
  - `--breakpoint-lg`: 992px
  - `--breakpoint-xl`: 1200px

## Components

### Buttons

Base class: `.btn`

Modifiers:
- `.btn--primary`
- `.btn--secondary`
- `.btn--light`
- `.btn--lg`

### Card

Use the `.card` class. Cards gain elevation on hover.

### Inputs

Use `.input` for text inputs. Provides focus ring and consistent spacing.

### Hero

`<div class="hero">` creates a full-width section with the primary color.

---

Refer to `custom.css` for additional component styles and utilities.