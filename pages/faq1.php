<?php $extra_css = '/assets/css/faq.css'; ?>
<?php require_once __DIR__ . '/../includes/header1.php'; ?>

<div class="container-fluid px-4 px-lg-5 pt-4">
  <div class="faq-page-wrap">
    <h2 class="faq-title">FAQ</h2>

    <div class="faq-panel">
      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>Who can apply to my project ?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          Answer Goes Here
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>Can I filter teammates by specific skills?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          Answer Goes Here
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>What if a teammate doesn’t do any work?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          Answer Goes Here
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>How do matches work?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          Answer Goes Here
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>How do I report inappropriate behaviour?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          Answer Goes Here
        </div>
      </div>

      <div class="faq-item">
        <button class="faq-question" type="button">
          <span>Can I apply for multiple roles within the project ?</span>
          <span class="faq-plus">+</span>
        </button>
        <div class="faq-answer">
          Answer Goes Here
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