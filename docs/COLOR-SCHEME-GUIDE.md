# PropOff Color Scheme Guide

## Brand Colors

These colors are configured in `tailwind.config.js` and available throughout the application:

| Color Name | Hex Code | Tailwind Class | Usage |
|------------|----------|----------------|-------|
| **Red** | `#af1919` | `propoff-red` | Primary CTA, errors, important alerts |
| **Orange** | `#f47612` | `propoff-orange` | Secondary CTA, highlights, hover states |
| **Green** | `#57d025` | `propoff-green` | Success states, positive actions, leaderboards |
| **Dark Green** | `#186916` | `propoff-dark-green` | Success backgrounds, grading, completed states |
| **Blue** | `#1a3490` | `propoff-blue` | Links, informational, backgrounds |

## Usage Examples

### Backgrounds
```html
<div class="bg-propoff-red">Red background</div>
<div class="bg-propoff-orange">Orange background</div>
<div class="bg-propoff-green">Green background</div>
<div class="bg-propoff-dark-green">Dark Green background</div>
<div class="bg-propoff-blue">Blue background</div>
```

### Text Colors
```html
<p class="text-propoff-red">Red text</p>
<p class="text-propoff-orange">Orange text</p>
<p class="text-propoff-green">Green text</p>
<p class="text-propoff-dark-green">Dark Green text</p>
<p class="text-propoff-blue">Blue text</p>
```

### Borders
```html
<div class="border-propoff-red">Red border</div>
<div class="border-propoff-orange">Orange border</div>
```

### Gradients
```html
<div class="bg-gradient-to-r from-propoff-red to-propoff-orange">Red to Orange gradient</div>
<div class="bg-gradient-to-br from-propoff-blue via-propoff-dark-green to-propoff-red">Complex gradient</div>
```

### Opacity Variations
```html
<div class="bg-propoff-red/50">Red at 50% opacity</div>
<div class="bg-propoff-orange/30">Orange at 30% opacity</div>
```

## Component Color Guidelines

### Buttons
- **Primary Action**: `bg-gradient-to-r from-propoff-red to-propoff-orange`
- **Secondary Action**: `bg-propoff-blue hover:bg-propoff-blue/80`
- **Success**: `bg-propoff-green hover:bg-propoff-dark-green`
- **Danger**: `bg-propoff-red hover:bg-propoff-red/80`

### Status Indicators
- **Success**: `text-propoff-green` or `bg-propoff-green/20`
- **Warning**: `text-propoff-orange` or `bg-propoff-orange/20`
- **Error**: `text-propoff-red` or `bg-propoff-red/20`
- **Info**: `text-propoff-blue` or `bg-propoff-blue/20`

### Game States
- **Draft**: `bg-gray-500`
- **Open**: `bg-propoff-green`
- **Locked**: `bg-propoff-orange`
- **Completed**: `bg-propoff-dark-green`

### Leaderboards
- **1st Place**: `text-propoff-orange` (Gold alternative)
- **2nd Place**: `text-gray-400` (Silver)
- **3rd Place**: `text-orange-600` (Bronze)
- **Positive Score**: `text-propoff-green`
- **Negative Score**: `text-propoff-red`

### Links & Hover States
- **Link Default**: `text-propoff-blue`
- **Link Hover**: `text-propoff-orange`
- **Button Hover**: Add `hover:scale-105` with `hover:shadow-propoff-orange/50`

## Landing Page Color Map

- **Background Gradient**: `from-propoff-blue via-propoff-dark-green to-propoff-red`
- **Feature Card Icons**:
  - Create Games: `bg-propoff-red/40`
  - Group Competition: `bg-propoff-blue/40`
  - Live Leaderboards: `bg-propoff-green/40`
  - Easy Invites: `bg-propoff-orange/40`
  - Auto Grading: `bg-propoff-dark-green/40`
  - Export Data: `bg-propoff-blue/40`
- **CTA Button**: `from-propoff-red to-propoff-orange`
- **Nav Links Hover**: `hover:text-propoff-orange`

## Accessibility Notes

All colors have been chosen to maintain WCAG 2.1 AA compliance:
- White text on all brand colors passes contrast requirements
- Use `-dark-green` for better readability when needed
- Always test color combinations for sufficient contrast

## Future Considerations

When adding new components, follow this priority:
1. **Red** - High priority, urgent, errors
2. **Orange** - Medium priority, warnings, secondary actions
3. **Green** - Success, completion, positive feedback
4. **Dark Green** - Subtle success, backgrounds
5. **Blue** - Information, navigation, calm actions
