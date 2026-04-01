:root {
  --bg-color: #f7f5f0;
  --line-color: #d8d7cb;

  --dark-green: #7b8454;
  --mid-green: #a9b97b;
  --light-green: #bccb72;

  --text-color: #22251f;
  --text-soft: #6a6f63;
}

body {
  background-color: var(--bg-color);
  font-family: Arial, Helvetica, sans-serif;
  color: var(--text-color);
}

.content-area {
  padding: 22px 36px 36px;
}

.section-pill {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  background-color: var(--mid-green);
  color: #111;
  border-radius: 999px;
  padding: 8px 20px;
  min-height: 40px;
  font-size: 0.95rem;
  font-weight: 700;
  margin-bottom: 12px;
}

.section-line {
  border-top: 1px solid var(--line-color);
  margin: 14px 0 18px;
}

.project-card {
  background: var(--mid-green);
  border: 1px solid #96a66c;
  border-radius: 20px;
  padding: 22px 24px 18px;
  margin-top: 18px;
  max-width: 950px;
  position: relative;
}

.project-card + .project-card {
  margin-top: 26px;
}

.project-card-header {
  display: flex;
  justify-content: space-between;
  align-items: center;
  gap: 18px;
  padding-bottom: 12px;
  margin-bottom: 16px;
  border-bottom: 1px solid rgba(0, 0, 0, 0.25);
}

.project-card-header h2 {
  margin: 0;
  font-size: 1.55rem;
  font-weight: 700;
  color: #111;
  line-height: 1.2;
}

.project-card-header span {
  font-size: 0.9rem;
  font-weight: 600;
  color: #111;
  background: rgba(255, 255, 255, 0.35);
  padding: 5px 10px;
  border-radius: 999px;
  white-space: nowrap;
}

.project-card-body {
  display: grid;
  gap: 10px;
}

.project-card-body p {
  margin: 0;
  font-size: 0.98rem;
  line-height: 1.45;
  color: #1a1a1a;
}

.project-card-body strong {
  font-weight: 600;
}

.status {
  display: inline-block;
  font-size: 0.85rem;
  font-weight: 700;
  padding: 4px 10px;
  border-radius: 999px;
  margin-left: 6px;
}

.status-open {
  background-color: #d4edda;
  color: #155724;
}

.status-progress {
  background-color: #fff3cd;
  color: #856404;
}

.status-completed {
  background-color: #f8d7da;
  color: #721c24;
}

.project-card-actions {
  margin-top: 18px;
  display: flex;
  justify-content: flex-end;
  gap: 10px;
  flex-wrap: wrap;
}

.view-applications-btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  text-decoration: none;
  border-radius: 999px;
  min-height: 38px;
  padding: 0 14px;
  font-size: 0.92rem;
  font-weight: 700;
  background: #f8f6f1;
  border: 1px solid #d6d9cb;
  color: #111;
  transition: 0.2s ease;
}

.view-applications-btn:hover {
  transform: translateY(-1px);
  opacity: 0.96;
  color: #111;
}

.project-card-actions.completed {
  justify-content: flex-end;
}

.completed-badge {
  font-size: 0.85rem;
  font-weight: 700;
  color: #5f6556;
  background: rgba(255, 255, 255, 0.45);
  padding: 5px 10px;
  border-radius: 999px;
}

.joined-project {
  background: #b4c287;
  border-color: #9fae74;
}

.section-pill + .project-card {
  margin-top: 14px;
}

.project-card .project-card-actions:empty {
  display: none;
}

@media (max-width: 992px) {
  .content-area {
    padding: 20px 22px 30px;
  }

  .project-card {
    padding: 20px 18px 16px;
    border-radius: 18px;
  }

  .project-card-header {
    flex-direction: column;
    align-items: flex-start;
  }

  .project-card-header h2 {
    font-size: 1.4rem;
  }
}

@media (max-width: 576px) {
  .content-area {
    padding: 16px;
  }

  .section-pill,
  .view-applications-btn {
    width: 100%;
  }

  .project-card-actions {
    justify-content: stretch;
  }
}