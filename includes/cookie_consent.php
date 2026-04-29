<?php if (empty($_COOKIE['cookie_consent'])): ?>
<div id="cookie-banner" class="cookie-banner">
  <div class="cookie-text">
    <strong>Cookie Notice</strong>
    We use cookies to keep you logged in and improve your experience. By continuing to use MindLink, you agree to our use of cookies.
  </div>
  <div class="cookie-actions">
    <button class="cookie-btn-accept" onclick="acceptCookies()">Accept</button>
    <button class="cookie-btn-decline" onclick="declineCookies()">Decline</button>
  </div>
</div>
<script>
  // Sets consent cookie for 6 months
  function acceptCookies() {
    document.cookie = "cookie_consent=1; path=/; max-age=" + (60 * 60 * 24 * 180);
    document.getElementById('cookie-banner').style.display = 'none';
  }
  // Hides banner without setting a cookie (will reappear next visit)
  function declineCookies() {
    document.getElementById('cookie-banner').style.display = 'none';
  }
</script>
<?php endif; ?>
