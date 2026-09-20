# Taxi Lottie Animation Setup

## Current Status
✅ The Lottie integration code is already in place in `landing/index.vue`
❌ The `taxi.json` file currently contains an anchor animation, not a taxi animation

## Steps to Complete the Integration

### Option 1: Download from LottieFiles (Recommended)
1. Visit: https://lottiefiles.com/search?q=taxi&category=animations
2. Find a taxi animation you like (free options available)
3. Click "Download" → Select "Lottie JSON"
4. Save the downloaded file as `taxi.json`
5. Replace the existing file at: `resources/js/Components/widgets/taxi.json`

### Option 2: Use a Direct URL
You can use a CDN URL directly in the code. Popular taxi animations:

**Free Taxi Animations:**
- https://lottiefiles.com/animations/taxi-car-animation
- https://lottiefiles.com/animations/taxi-cab
- https://lottiefiles.com/animations/delivery-car

### Option 3: Generate Custom Animation
If you want a specific style, you can:
1. Use Adobe After Effects with Bodymovin plugin
2. Use online tools like Haiku Animator
3. Commission a custom animation from LottieFiles creators

## Current Integration Code
The code in `landing/index.vue` is already set up:

```javascript
// Lines 15-16: Import
import lottie from 'lottie-web';
import taxiAnimation from '@/Components/widgets/taxi.json';

// Lines 43-51: Initialization
mounted() {
    if (this.$refs.taxiLottie) {
        lottie.loadAnimation({
            container: this.$refs.taxiLottie,
            renderer: 'svg',
            loop: true,
            autoplay: true,
            animationData: taxiAnimation
        });
    }
}

// Line 98: Display container
<div ref="taxiLottie" class="mx-auto mb-4" style="max-width: 400px;"></div>
```

## Quick Fix
Simply replace `taxi.json` with a proper taxi animation file and refresh your page!
