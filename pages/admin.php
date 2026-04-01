<?php $extra_css = '../assets/css/admin.css'; ?>
<?php require_once __DIR__ . '/../includes/header2.php'; ?>

<div class="main-content">
    <div class="content-area">
      <div class="admin-main-grid">
        <div class="admin-left">

          <div class="section-pill admin-pill">Platform Overview</div>

          <div class="overview-cards">
            <div class="overview-card">
              <div class="overview-icon user-icon">
                <div class="user-icon-head"></div>
                <div class="user-icon-body"></div>
              </div>
              <div class="overview-content">
                <div class="overview-number">1,234</div>
                <div class="overview-label">Total<br>Users</div>
              </div>
            </div>

            <div class="overview-card">
              <div class="overview-icon folder-icon">
                <div class="folder-back"></div>
                <div class="folder-front"></div>
              </div>
              <div class="overview-content">
                <div class="overview-number">56</div>
                <div class="overview-label">Active<br>Projects</div>
              </div>
            </div>

            <div class="overview-card">
              <div class="overview-icon message-icon">
                <div class="message-bubble"></div>
                <div class="message-dot dot-1"></div>
                <div class="message-dot dot-2"></div>
                <div class="message-dot dot-3"></div>
              </div>
              <div class="overview-content">
                <div class="overview-number">789</div>
                <div class="overview-label">Messages<br>Today</div>
              </div>
            </div>

            <div class="overview-card">
              <div class="overview-icon flag-icon">
                <div class="flag-pole"></div>
                <div class="flag-shape"></div>
              </div>
              <div class="overview-content">
                <div class="overview-number">10</div>
                <div class="overview-label">Reports</div>
              </div>
            </div>
          </div>

          <div class="section-line admin-line"></div>

          <div class="section-pill admin-pill">User Management</div>

          <div class="admin-search-row">
            <div class="admin-search-box">Search users...</div>
            <div class="admin-filter-box">
              <span>Filter: Active</span>
              <img src="/assets/img/search_icon.png" class="admin-small-search" alt="Search">
            </div>
          </div>

          <div class="admin-table-card">
            <div class="admin-table-header">
              <div>Name</div>
              <div>Course</div>
              <div>Year</div>
              <div>Status</div>
              <div>Actions</div>
            </div>

            <div class="admin-table-row">
              <div>John Doe</div>
              <div>CS</div>
              <div>3</div>
              <div class="status-active">Active</div>
              <div class="admin-actions">
                <span class="action-link">View</span>
                <span class="action-dot">•</span>
                <span class="action-link danger">Suspend</span>
              </div>
            </div>

            <div class="admin-table-row">
              <div>Jane Doe</div>
              <div>EN</div>
              <div>4</div>
              <div class="status-reported">Reported</div>
              <div class="admin-actions">
                <span class="action-link">Review</span>
              </div>
            </div>

            <div class="admin-table-row">
              <div>Mike Smith</div>
              <div>CS</div>
              <div>2</div>
              <div class="status-suspended">Suspended</div>
              <div class="admin-actions">
                <span class="action-link success">Reinstate</span>
              </div>
            </div>

            <div class="admin-table-row">
              <div>Mary Smith</div>
              <div>IT</div>
              <div>3</div>
              <div class="status-active">Active</div>
              <div class="admin-actions">
                <span class="action-link">View</span>
                <span class="action-dot">•</span>
                <span class="action-link danger">Suspend</span>
              </div>
            </div>
          </div>

          <div class="section-pill admin-pill admin-project-pill">Project Moderation</div>

          <div class="moderation-card">
            <div class="moderation-header">
              <div>Software Development</div>
              <div class="admin-actions">
                <span class="action-link light-link">Review</span>
                <span class="action-dot light-dot">•</span>
                <span class="action-link light-link danger-light">Remove</span>
              </div>
            </div>

            <div class="moderation-body">
              <div class="moderation-left">
                <p>Report : Inappropriate Content</p>
                <p>Report : Inactive Students</p>
              </div>
              <div class="moderation-right">
                <p>Reported by: 1 user</p>
                <p>Reported by: 2 users</p>
              </div>
            </div>
          </div>
        </div>

        <div class="admin-right">
          <div class="report-card">
            <div class="report-card-header">Reports &amp; Safety</div>
            <div class="report-card-body">
              <div class="report-title">User Report</div>
              <div class="report-divider"></div>
              <div class="report-name">Jane Doe</div>
              <div class="report-reason">Reason: Harassment</div>
              <div class="report-divider bottom-divider"></div>
              <div class="report-actions">
                <button class="report-btn">Review</button>
                <button class="report-btn">Resolve</button>
              </div>
            </div>
          </div>

          <div class="report-card second-report-card">
            <div class="report-card-header">Reports &amp; Safety</div>
            <div class="report-card-body">
              <div class="report-title">Project Report</div>
              <div class="report-divider"></div>
              <div class="report-name">Mobile App</div>
              <div class="report-reason">Reason: Misleading Description</div>
              <div class="report-divider bottom-divider"></div>
              <div class="report-actions">
                <button class="report-btn single-btn">Review</button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>