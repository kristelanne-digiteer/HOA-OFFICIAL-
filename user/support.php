<?php
session_name('HOA_USER_SESSION');
session_start();
include("../config/db.php");
include("../includes/user_header.php");

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle ticket submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_ticket'])) {
    $subject = mysqli_real_escape_string($conn, trim($_POST['subject']));
    $message = mysqli_real_escape_string($conn, trim($_POST['message']));
    
    if (!empty($subject) && !empty($message)) {
        mysqli_query($conn, "
            INSERT INTO support_tickets (user_id, subject, message, status, created_at)
            VALUES ($user_id, '$subject', '$message', 'open', NOW())
        ");
        
        header("Location: support.php?tab=contact&submitted=1");
        exit;
    }
}

// Fetch FAQs
$faqs_query = mysqli_query($conn, "SELECT * FROM faqs ORDER BY category, faq_id");
$faqs_by_category = [];
if ($faqs_query) {
    while ($faq = mysqli_fetch_assoc($faqs_query)) {
        $faqs_by_category[$faq['category']][] = $faq;
    }
}

// Fetch Guides
$guides_query = mysqli_query($conn, "SELECT * FROM guides ORDER BY category, guide_id");

// Fetch user's tickets
$tickets_query = mysqli_query($conn, "
    SELECT st.*, 
            (SELECT COUNT(*) FROM ticket_replies WHERE ticket_id = st.ticket_id) as reply_count
    FROM support_tickets st
    WHERE st.user_id = $user_id
    ORDER BY st.created_at DESC
");
?>

<style>
.page-header {
    margin-bottom: 32px;
}

.page-header h1 {
    font-size: 28px;
    margin-bottom: 8px;
    color: white;
}

.page-header p {
    color: #9ca3af;
    font-size: 15px;
}

.tab-nav {
    display: flex;
    gap: 8px;
    border-bottom: 2px solid #1e3a3a;
    margin-bottom: 32px;
    flex-wrap: wrap;
}

.tab-btn {
    padding: 12px 24px;
    background: transparent;
    border: none;
    border-bottom: 3px solid transparent;
    color: #9ca3af;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
    margin-bottom: -2px;
}

.tab-btn:hover {
    color: white;
}

.tab-btn.active {
    color: #dc1623;
    border-bottom-color: #dc1623;
}

.tab-content {
    display: none;
}

.tab-content.active {
    display: block;
}

/* FAQs Styles */
.faq-category {
    margin-bottom: 32px;
}

.faq-category h3 {
    font-size: 18px;
    color: #dc1623;
    margin-bottom: 16px;
    padding-bottom: 8px;
    border-bottom: 1px solid #1e3a3a;
}

.faq-item {
    background: #0f1f22;
    border: 1px solid #1e3a3a;
    border-radius: 8px;
    margin-bottom: 12px;
    overflow: hidden;
}

.faq-question {
    padding: 16px;
    cursor: pointer;
    display: flex;
    justify-content: space-between;
    align-items: center;
    font-weight: bold;
    color: white;
    transition: background 0.2s;
}

.faq-question:hover {
    background: #0a1517;
}

.faq-answer {
    padding: 0 16px;
    max-height: 0;
    overflow: hidden;
    transition: all 0.3s;
    color: #d1d5db;
    line-height: 1.6;
}

.faq-answer.open {
    padding: 16px;
    max-height: 500px;
}

.faq-icon {
    font-size: 18px;
    transition: transform 0.3s;
}

.faq-icon.open {
    transform: rotate(180deg);
}

/* Guides Styles */
.guides-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
    gap: 20px;
}

.guide-card {
    background: #0f1f22;
    border: 1px solid #1e3a3a;
    border-radius: 12px;
    padding: 24px;
    transition: all 0.3s;
}

.guide-card:hover {
    border-color: #dc1623;
    transform: translateY(-2px);
}

.guide-category-badge {
    display: inline-block;
    padding: 4px 12px;
    background: #450a0a;
    color: #dc1623;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
    text-transform: uppercase;
    margin-bottom: 12px;
}

.guide-card h4 {
    font-size: 18px;
    margin-bottom: 16px;
    color: white;
}

.guide-content {
    color: #d1d5db;
    line-height: 1.6;
    font-size: 14px;
}

.guide-content strong {
    color: white;
}

.btn-view-guide {
    display: inline-block;
    margin-top: 16px;
    padding: 8px 16px;
    background: #1e3a5f;
    color: #3b82f6;
    border-radius: 6px;
    font-size: 13px;
    font-weight: bold;
    text-decoration: none;
    transition: all 0.2s;
}

.btn-view-guide:hover {
    background: #2c4a7f;
}

/* Contact Support Styles */
.contact-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

@media (max-width: 768px) {
    .contact-grid {
        grid-template-columns: 1fr;
    }
}

.contact-card {
    background: #0f1f22;
    border: 1px solid #1e3a3a;
    border-radius: 12px;
    padding: 24px;
}

.contact-card h3 {
    font-size: 18px;
    margin-bottom: 20px;
    color: white;
}

.form-group {
    margin-bottom: 16px;
}

.form-group label {
    display: block;
    color: #9ca3af;
    font-size: 13px;
    margin-bottom: 6px;
    font-weight: bold;
}

.form-group select,
.form-group textarea {
    width: 100%;
    padding: 10px 12px;
    background: #0a1517;
    border: 1px solid #1e3a3a;
    border-radius: 6px;
    color: white;
    font-size: 14px;
    font-family: inherit;
}

.form-group textarea {
    resize: vertical;
    min-height: 120px;
}

.form-group select:focus,
.form-group textarea:focus {
    outline: none;
    border-color: #dc1623;
}

.btn-submit {
    width: 100%;
    padding: 12px;
    background: #dc1623;
    color: white;
    border: none;
    border-radius: 6px;
    font-size: 14px;
    font-weight: bold;
    cursor: pointer;
    transition: all 0.2s;
}

.btn-submit:hover {
    background: #b91319;
}

.ticket-list {
    max-height: 500px;
    overflow-y: auto;
}

/* GINAWANG CLICKABLE AT MAY HOVER EFFECT ANG TICKET LINKS */
.ticket-link {
    text-decoration: none;
    display: block;
    margin-bottom: 12px;
    outline: none;
}

.ticket-item {
    background: #0a1517;
    padding: 16px;
    border-radius: 8px;
    border-left: 3px solid #1e3a3a;
    cursor: pointer;
    transition: background 0.2s, transform 0.1s;
}

.ticket-item:hover {
    background: #0f1f22;
    transform: translateX(3px);
}

.ticket-item.open { border-left-color: #ef4444; }
.ticket-item.in_progress { border-left-color: #f59e0b; }
.ticket-item.closed { border-left-color: #10b981; }
.ticket-item.rejected { border-left-color: #ef4444; }

.ticket-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 8px;
}

.ticket-subject {
    font-weight: bold;
    color: white;
    font-size: 15px;
}

.ticket-status {
    font-size: 11px;
    padding: 3px 10px;
    border-radius: 12px;
    font-weight: bold;
}

.status-open { background: #450a0a; color: #ef4444; }
.status-in_progress { background: #451a03; color: #f59e0b; }
.status-closed { background: #064e3b; color: #10b981; }
.status-rejected { background: #450a0a; color: #ef4444; }

.ticket-message {
    color: #9ca3af;
    font-size: 13px;
    margin-bottom: 8px;
}

.ticket-date {
    color: #6b7280;
    font-size: 12px;
}

.reply-badge {
    display: inline-block;
    margin-left: 8px;
    padding: 2px 8px;
    background: #1e3a5f;
    color: #3b82f6;
    border-radius: 10px;
    font-size: 11px;
}

.alert-success {
    background: #064e3b;
    color: #10b981;
    padding: 16px;
    border-radius: 8px;
    margin-bottom: 24px;
    font-size: 14px;
}

.empty-state {
    text-align: center;
    padding: 40px;
    color: #9ca3af;
}

.empty-state-icon {
    font-size: 48px;
    margin-bottom: 16px;
}
</style>

<div class="page-header">
    <h1>Help & Support</h1>
    <p>Get help with payments, documents, rentals, and more</p>
</div>

<?php if (isset($_GET['submitted'])): ?>
<div class="alert-success">
    ✓ Your support ticket has been submitted successfully! Our team will respond shortly.
</div>
<?php endif; ?>

<div class="tab-nav">
    <button class="tab-btn <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'faqs') ? 'active' : ''; ?>" data-tab="faqs" onclick="switchTab('faqs')">
        📚 FAQs
    </button>
    <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'guides') ? 'active' : ''; ?>" data-tab="guides" onclick="switchTab('guides')">
        📖 Guides
    </button>
    <button class="tab-btn <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'contact') ? 'active' : ''; ?>" data-tab="contact" onclick="switchTab('contact')">
        💬 Contact Support
    </button>
</div>

<div class="tab-content <?php echo (!isset($_GET['tab']) || $_GET['tab'] == 'faqs') ? 'active' : ''; ?>" id="tab-faqs">
    <?php if (empty($faqs_by_category)): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📚</div>
            <p>No FAQs available yet.</p>
        </div>
    <?php else: ?>
        <?php foreach ($faqs_by_category as $category => $faqs): ?>
        <div class="faq-category">
            <h3><?= htmlspecialchars($category) ?></h3>
            <?php foreach ($faqs as $faq): ?>
            <div class="faq-item">
                <div class="faq-question" onclick="toggleFaq(this)">
                    <span><?= htmlspecialchars($faq['question']) ?></span>
                    <span class="faq-icon">▼</span>
                </div>
                <div class="faq-answer">
                    <?= nl2br(htmlspecialchars($faq['answer'])) ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<div class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'guides') ? 'active' : ''; ?>" id="tab-guides">
    <?php if (!$guides_query || mysqli_num_rows($guides_query) == 0): ?>
        <div class="empty-state">
            <div class="empty-state-icon">📖</div>
            <p>No guides available yet.</p>
        </div>
    <?php else: ?>
    <div class="guides-grid">
        <?php while ($guide = mysqli_fetch_assoc($guides_query)): ?>
        <div class="guide-card">
            <span class="guide-category-badge"><?= htmlspecialchars($guide['category']) ?></span>
            <h4><?= htmlspecialchars($guide['title']) ?></h4>
            <div class="guide-content">
                <?= nl2br(htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($guide['content'], 0, 200, '...') : substr($guide['content'], 0, 200) . '...')) ?>
            </div>
            <?php if ($guide['video_url']): ?>
                <a href="<?= htmlspecialchars($guide['video_url']) ?>" target="_blank" class="btn-view-guide">
                    🎥 Watch Video
                </a>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
</div>

<div class="tab-content <?php echo (isset($_GET['tab']) && $_GET['tab'] == 'contact') ? 'active' : ''; ?>" id="tab-contact">
    <div class="contact-grid">
        <div class="contact-card">
            <h3>💬 Submit Support Ticket</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Subject *</label>
                    <select name="subject" required>
                        <option value="">Select issue type...</option>
                        <option value="Incorrect Billing">Incorrect Billing</option>
                        <option value="Payment Issue">Payment Issue</option>
                        <option value="Document Follow-up">Document Follow-up</option>
                        <option value="Rental Inquiry">Rental Inquiry</option>
                        <option value="Account Issue">Account Issue</option>
                        <option value="Technical Support">Technical Support</option>
                        <option value="General Inquiry">General Inquiry</option>
                        <option value="Other">Other</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label>Message *</label>
                    <textarea name="message" placeholder="Describe your concern in detail..." required></textarea>
                </div>
                
                <button type="submit" name="submit_ticket" class="btn-submit">
                    Send Ticket
                </button>
            </form>
        </div>

        <div class="contact-card">
            <h3>📋 Your Tickets</h3>
            <div class="ticket-list">
                <?php if (!$tickets_query || mysqli_num_rows($tickets_query) == 0): ?>
                    <div class="empty-state">
                        <div class="empty-state-icon">📭</div>
                        <p>No tickets submitted yet.</p>
                    </div>
                <?php else: ?>
                    <?php while ($ticket = mysqli_fetch_assoc($tickets_query)): ?>
                    
                    <a href="view_ticket.php?id=<?= $ticket['ticket_id'] ?>" class="ticket-link">
                        <div class="ticket-item <?= $ticket['status'] ?>">
                            <div class="ticket-header">
                                <span class="ticket-subject">
                                    <?= htmlspecialchars($ticket['subject']) ?>
                                    <?php if ($ticket['reply_count'] > 0): ?>
                                        <span class="reply-badge"><?= $ticket['reply_count'] ?> reply</span>
                                    <?php endif; ?>
                                </span>
                                <span class="ticket-status status-<?= $ticket['status'] ?>">
                                    <?= ucwords(str_replace('_', ' ', $ticket['status'])) ?>
                                </span>
                            </div>
                            <div class="ticket-message">
                                <?= htmlspecialchars(function_exists('mb_strimwidth') ? mb_strimwidth($ticket['message'], 0, 100, '...') : substr($ticket['message'], 0, 100) . '...') ?>
                            </div>
                            <div class="ticket-date">
                                <?= date('M d, Y \a\t g:i A', strtotime($ticket['created_at'])) ?>
                            </div>
                        </div>
                    </a>
                    
                    <?php endwhile; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabName) {
    // Update URL
    const url = new URL(window.location);
    url.searchParams.set('tab', tabName);
    window.history.pushState({}, '', url);
    
    // Hide all tabs
    document.querySelectorAll('.tab-content').forEach(tab => {
        tab.classList.remove('active');
    });
    
    document.querySelectorAll('.tab-btn').forEach(btn => {
        btn.classList.remove('active');
    });
    
    // Show selected tab
    document.getElementById('tab-' + tabName).classList.add('active');
    
    // Safe dynamic button activation
    const currentBtn = document.querySelector(`.tab-btn[data-tab="${tabName}"]`);
    if(currentBtn) currentBtn.classList.add('active');
}

function toggleFaq(element) {
    const answer = element.nextElementSibling;
    const icon = element.querySelector('.faq-icon');
    
    answer.classList.toggle('open');
    if (icon) icon.classList.toggle('open');
}
</script>

<?php include("../includes/user_footer.php"); ?>