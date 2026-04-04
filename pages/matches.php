<?php
$pageTitle = "Matches - Mindlink";
$extra_css = '/assets/css/matches.css';
require_once __DIR__ . '/../includes/header2.php';
?>

<div class="matches-container">
  <div class="section-header">
    <h2>Strengthen your Connection!</h2>
    <p>Find potential matches with students you've worked with. Chat, connect, and see if your collaboration can turn into something more!</p>
  </div>
  <div class="divider"></div>

  <div class="subheader">Profiles from completed projects:</div>

  <div class="profiles-grid">
    <!-- Mike -->
    <div class="profile-card">
      <div class="avatar-wrapper">
        <div class="avatar-circle">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12,12A4,4 0 1,0 8,8A4,4 0 0,0 12,12ZM12,14C9.33,14 4,15.34 4,18V20H20V18C20,15.34 14.67,14 12,14Z"/>
          </svg>
        </div>
        <div class="heart-icon">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5c0-2.5 1.99-4.5 4.5-4.5c1.74 0 3.41 1.01 4.22 2.61C11.09 5.01 12.76 4 14.5 4C17.01 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
          </svg>
        </div>
        <span class="age-number">22</span>
      </div>
      <div class="profile-name">Mike Smith</div>
      <div class="profile-course">Computer Science</div>
      <div class="skill-box">Web Project · PHP · MySQL</div>
      <button class="match-btn">MATCH!</button>
    </div>

    <!-- Jane -->
    <div class="profile-card">
      <div class="avatar-wrapper">
        <div class="avatar-circle">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12,12A4,4 0 1,0 8,8A4,4 0 0,0 12,12ZM12,14C9.33,14 4,15.34 4,18V20H20V18C20,15.34 14.67,14 12,14Z"/>
          </svg>
        </div>
        <div class="heart-icon">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5c0-2.5 1.99-4.5 4.5-4.5c1.74 0 3.41 1.01 4.22 2.61C11.09 5.01 12.76 4 14.5 4C17.01 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
          </svg>
        </div>
        <span class="age-number">21</span>
      </div>
      <div class="profile-name">Jane Smith</div>
      <div class="profile-course">Information Technology</div>
      <div class="skill-box">Web Project · PHP · MySQL</div>
      <button class="match-btn">MATCH!</button>
    </div>

    <!-- John -->
    <div class="profile-card">
      <div class="avatar-wrapper">
        <div class="avatar-circle">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12,12A4,4 0 1,0 8,8A4,4 0 0,0 12,12ZM12,14C9.33,14 4,15.34 4,18V20H20V18C20,15.34 14.67,14 12,14Z"/>
          </svg>
        </div>
        <div class="heart-icon">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5c0-2.5 1.99-4.5 4.5-4.5c1.74 0 3.41 1.01 4.22 2.61C11.09 5.01 12.76 4 14.5 4C17.01 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
          </svg>
        </div>
        <span class="age-number">24</span>
      </div>
      <div class="profile-name">John Doe</div>
      <div class="profile-course">Games Development</div>
      <div class="skill-box">Web Project · PHP · MySQL</div>
      <button class="match-btn">MATCH!</button>
    </div>
  </div>

  <div class="divider"></div>

  <div class="subheader">Match Requests:</div>

  <div class="profiles-grid">
    <!-- Lucy -->
    <div class="profile-card">
      <div class="avatar-wrapper">
        <div class="avatar-circle">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12,12A4,4 0 1,0 8,8A4,4 0 0,0 12,12ZM12,14C9.33,14 4,15.34 4,18V20H20V18C20,15.34 14.67,14 12,14Z"/>
          </svg>
        </div>
        <div class="heart-icon">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5c0-2.5 1.99-4.5 4.5-4.5c1.74 0 3.41 1.01 4.22 2.61C11.09 5.01 12.76 4 14.5 4C17.01 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
          </svg>
        </div>
        <span class="age-number">20</span>
      </div>
      <div class="profile-name">Lucy Smith</div>
      <div class="profile-course">Cyber Security</div>
      <div class="skill-box">Software Testing · Jira · Trello</div>
      <button class="match-btn accept-btn">ACCEPT</button>
    </div>

    <!-- Sarah -->
    <div class="profile-card">
      <div class="avatar-wrapper">
        <div class="avatar-circle">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12,12A4,4 0 1,0 8,8A4,4 0 0,0 12,12ZM12,14C9.33,14 4,15.34 4,18V20H20V18C20,15.34 14.67,14 12,14Z"/>
          </svg>
        </div>
        <div class="heart-icon">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5c0-2.5 1.99-4.5 4.5-4.5c1.74 0 3.41 1.01 4.22 2.61C11.09 5.01 12.76 4 14.5 4C17.01 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
          </svg>
        </div>
        <span class="age-number">19</span>
      </div>
      <div class="profile-name">Sarah Doe</div>
      <div class="profile-course">Computer Science</div>
      <div class="skill-box">Website Design · HTML · CSS</div>
      <button class="match-btn accept-btn">ACCEPT</button>
    </div>

    <!-- Jane Doe -->
    <div class="profile-card">
      <div class="avatar-wrapper">
        <div class="avatar-circle">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12,12A4,4 0 1,0 8,8A4,4 0 0,0 12,12ZM12,14C9.33,14 4,15.34 4,18V20H20V18C20,15.34 14.67,14 12,14Z"/>
          </svg>
        </div>
        <div class="heart-icon">
          <svg xmlns="[w3.org](http://www.w3.org/2000/svg)" viewBox="0 0 24 24">
            <path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5c0-2.5 1.99-4.5 4.5-4.5c1.74 0 3.41 1.01 4.22 2.61C11.09 5.01 12.76 4 14.5 4C17.01 4 19 6 19 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/>
          </svg>
        </div>
        <span class="age-number">21</span>
      </div>
      <div class="profile-name">Jane Doe</div>
      <div class="profile-course">Business</div>
      <div class="skill-box">Project Management · Trello · Kanban</div>
      <button class="match-btn accept-btn">ACCEPT</button>
    </div>
  </div>

  <div class="how-it-works">
    <h3>How does it work?</h3>
    <ul>
      <li>Review profiles of previous teammates.</li>
      <li>Match with a profile to indicate potential interest.</li>
      <li>If they also match with you, start chatting and get to know each other.</li>
    </ul>
  </div>

</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
