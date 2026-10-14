<?php
/**
 * PWA Meta Tags & Service Worker Registration
 * Include this file in the <head> section of all public pages.
 */
?>
<!-- PWA Meta Tags -->
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="default">
<meta name="apple-mobile-web-app-title" content="Asset NHM">
<link rel="apple-touch-icon" href="PWA/icon.png">
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#2563eb">

<!-- Service Worker Registration -->
<script>
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      // Registering sw.js from the root folder for full scope access
      navigator.serviceWorker.register('sw.js')
        .then(reg => console.log('PWA Service Worker registered with scope:', reg.scope))
        .catch(err => console.error('PWA Service Worker registration failed:', err));
    });
  }
</script>
