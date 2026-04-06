<?php
$extra_css = '/assets/css/faq.css';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (isset($_SESSION['user_id'])) {
    require_once __DIR__ . '/../includes/header2.php';
} else {
    require_once __DIR__ . '/../includes/header1.php';
}
?>

<div class="container-fluid px-4 px-lg-5 pt-4">
  <div class="faq-page-wrap">
    <h2 class="faq-title">FAQ</h2>

    <div class="faq-panel">
      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>Who can apply to my project?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          Any logged-in user can apply to an open role, as long as the project is not completed and they are not the project owner.
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>Can I filter teammates by specific skills?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          You can search projects and users using keywords and tags such as PHP, MySQL, or UI Design. Skills listed on user profiles can also help you find suitable teammates.
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>What if a teammate doesn’t do any work?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          You can communicate through the platform first. If problems continue, use the reporting tools available on the site to flag inappropriate behaviour or serious teamwork issues.
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>How do matches work?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          Matches are based on completed teamwork history and user preferences. After finishing projects, users can connect with past teammates and build future collaboration opportunities.
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>How do I report inappropriate behaviour?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          Use the reporting feature on the platform to report users who behave inappropriately. Reports can then be reviewed by admins.
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>Can I apply for multiple roles within the project?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          You can apply to roles that are still open, but duplicate applications to the same role are prevented. Project owners review applications and decide who to accept.
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  const faqQuestions = document.querySelectorAll('.faq-question');

  faqQuestions.forEach(question => {
    question.addEventListener('click', () => {
      const item = question.parentElement;
      const plus = question.querySelector('.faq-plus');

      item.classList.toggle('active');
      plus.textContent = item.classList.contains('active') ? '−' : '+';
    });
  });
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>