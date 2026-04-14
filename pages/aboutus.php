<?php
require_once __DIR__ . '/../config/session.php';

$logged_in = isset($_SESSION['user_id']);
$extra_css = '/assets/css/aboutus.css';

if ($logged_in) {
    require_once __DIR__ . '/../includes/header2.php';
} else {
    require_once __DIR__ . '/../includes/header1.php';
}
?>

<main class="<?php echo $logged_in ? 'col-lg-10 content-area' : 'container mt-5'; ?>">
    <br>

    <h2 class="title">Stop gambling on groupmates, build a team that actually works.</h2>
    <h2 class="subtitle">MindLink helps students find better collaborators, and maybe something more...</h2>

    <br>

    <h6 class="paragraph">
        MindLink is a university student platform designed to remove the uncertainty from group work.
    </h6>
    <h6 class="paragraph">
        Instead of relying on luck, students can explore projects, discover teammates, review profiles,
        and connect with people who match their skills, interests, and goals.
    </h6>
    <h6 class="paragraph">
        Once a project is completed, the platform also offers a post-project connection feature
        based on mutual interest and consent.
    </h6>

    <br>
    <hr>

    <section class="about-section">
        <h3 class="about-section-title">What the Platform Offers</h3>
        <p class="about-section-subtitle">
            The key features that make MindLink a better way to approach student collaboration.
        </p>
        <hr>

        <div class="row mt-3 g-3">
            <div class="col-md-6 col-lg-3">
                <div class="card card-1 h-100 p-4">
                    <span class="card-title">PROFILES</span>
                    <p>
                        Build a profile with your course, year, bio, skills, and interests so other students
                        can quickly understand what you bring to a team.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card card-2 h-100 p-4">
                    <span class="card-title">PROJECTS</span>
                    <p>
                        Browse active projects, review important details, and find opportunities that match
                        your interests, goals, and experience.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card card-3 h-100 p-4">
                    <span class="card-title">MESSAGING</span>
                    <p>
                        Communicate through internal project chats and private messaging without needing to
                        move conversations outside the platform too early.
                    </p>
                </div>
            </div>

            <div class="col-md-6 col-lg-3">
                <div class="card card-4 h-100 p-4">
                    <span class="card-title">MATCHES</span>
                    <p>
                        Connect further with past project teammates or students with similar hobbies and interests.
                        After surviving and completing a project together, certain connections might just be worth keeping.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <br>

    <section class="about-section">
        <h3 class="about-section-title">Why MindLink?</h3>
        <p class="about-section-subtitle">
            A platform designed to make group work less random and more reliable.
        </p>
        <hr>

        <div class="about-panel panel-1">
            <p>
                Group work often becomes stressful because students do not have enough information before joining a team.
                MindLink helps solve that by giving users more visibility before they commit.
            </p>
            <p>
                By combining project discovery, profile information, search tools, messaging, and matches,
                the platform helps students make more informed choices when choosing collaborators.
            </p>
        </div>
    </section>

    <br>

    <section class="about-section">
        <h3 class="about-section-title">How It Works</h3>
        <p class="about-section-subtitle">
            From finding teammates to building projects and forming stronger connections.
        </p>
        <hr>

        <div class="about-panel panel-2">
            <p>
                Students can create a profile, browse available projects, search for teammates, and apply to join teams
                that match their skills and interests.
            </p>
            <p>
                Project owners can post ideas, define roles, review applications, and build a team around the people
                best suited for the work.
            </p>
            <p>
                Once a project is completed, teammates can leave feedback, and if there is mutual interest,
                the post-project chemistry feature gives them the chance to connect further.
            </p>
        </div>
    </section>

    <br>

    <section class="about-section">
        <h3 class="about-section-title">Safety and Trust</h3>
        <p class="about-section-subtitle">
            Built to support a safer and more respectful student environment.
        </p>
        <hr>

        <div class="about-panel panel-3">
            <p>
                MindLink keeps collaboration communication inside the platform and supports safer interactions
                through features like internal messaging, reporting, blocking, and moderation.
            </p>
            <p>
                The post-project connection feature is also designed around consent. It only becomes available after
                collaboration is complete, and only works when both people choose it.
            </p>
        </div>
    </section>

    <br>

    <section class="about-section">
        <h3 class="about-section-title">Who It Is For</h3>
        <p class="about-section-subtitle">
            Created for university students looking for better ways to work and connect.
        </p>
        <hr>

        <div class="about-panel panel-4">
            <p>
                MindLink is for students looking for teammates for coursework, hackathons, development projects,
                and other collaborative university work.
            </p>
            <p>
                Whether you already have an idea or want to join someone else’s project, the platform is designed
                to help you find the right people faster and with more confidence.
            </p>
        </div>
    </section>

    <br><br>

    <div class="text-center about-final-cta">
        <h3 class="about-final-title">Start building better teams with MindLink</h3>

        <p class="about-final-text">
            Find projects, discover teammates, and create stronger collaborations in one place.
        </p>

        <?php if ($logged_in): ?>
            <a href="/pages/projects.php" class="btn btn-signup px-4 py-2 mt-2">Browse Projects</a>
        <?php else: ?>
            <a href="/pages/sign_up.php" class="btn btn-signup px-4 py-2 mt-2">Get Started</a>
        <?php endif; ?>
    </div>

    <br><br>
</main>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>