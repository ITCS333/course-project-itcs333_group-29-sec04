// --- Global Data Store ---
// Stores the currently viewed topic ID and all replies
let currentTopicId = null;
let currentReplies = [];

// --- API URLs ---
// Endpoints for topics and replies
const TOPIC_API_URL = './api/index.php?resource=topics';
const REPLY_API_URL = './api/index.php?resource=replies';

// --- Element Selections ---
// DOM elements for displaying topic and replies
const topicSubject = document.querySelector('#topic-subject');
const opMessage = document.querySelector('#op-message');
const opFooter = document.querySelector('#op-footer');
const replyListContainer = document.querySelector('#reply-list-container');
const replyForm = document.querySelector('#reply-form');
const newReplyText = document.querySelector('#new-reply');

// --- Helper Functions ---

/**
 * Get topic ID from URL query string
 */
function getTopicIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

/**
 * Fetch topic data from API
 */
async function fetchTopic(topicId) {
    try {
        const response = await fetch(`${TOPIC_API_URL}&id=${topicId}`);
        if (!response.ok) throw new Error('Failed to fetch topic');
        const result = await response.json();
        return result.data || null;
    } catch (error) {
        console.error("Error fetching topic:", error);
        return null;
    }
}

/**
 * Fetch replies for the topic
 */
async function fetchReplies(topicId) {
    try {
        const response = await fetch(`${REPLY_API_URL}&topic_id=${topicId}`);
        if (!response.ok) throw new Error('Failed to fetch replies');
        const result = await response.json();
        currentReplies = result.data || [];
        renderReplies();
    } catch (error) {
        console.error("Error fetching replies:", error);
        replyListContainer.innerHTML = "<p>Error loading replies.</p>";
    }
}

/**
 * Render the original topic post and add Delete button functionality
 */
function renderOriginalPost(topic) {
    topicSubject.textContent = topic.subject;
    opMessage.textContent = topic.message;
    opFooter.textContent = `Posted by: ${topic.author} on ${topic.created_at}`;

    // Add Delete Topic button functionality
    const deleteBtn = document.querySelector('#original-post .contrast');
    if (deleteBtn) {
        deleteBtn.addEventListener('click', async () => {
            if (!confirm("Are you sure you want to delete this topic?")) return;

            try {
                const response = await fetch(`${TOPIC_API_URL}&id=${currentTopicId}`, {
                    method: 'DELETE'
                });
                if (!response.ok) throw new Error('Failed to delete topic');
                alert("Topic deleted successfully!");
                window.location.href = 'board.html'; // Redirect to topics list
            } catch (error) {
                console.error("Error deleting topic:", error);
                alert("Error deleting topic.");
            }
        });
    }
}

/**
 * Create a single reply article element
 */
function createReplyArticle(reply) {
    const article = document.createElement('article');

    const p = document.createElement('p');
    p.textContent = reply.text;
    article.appendChild(p);

    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${reply.author} on ${reply.created_at}`;
    article.appendChild(footer);

    // Actions (Delete button)
    const actionsDiv = document.createElement('div');
    const deleteBtn = document.createElement('button');
    deleteBtn.className = "contrast delete-reply-btn";
    deleteBtn.dataset.id = reply.id;
    deleteBtn.textContent = "Delete";
    actionsDiv.appendChild(deleteBtn);

    article.appendChild(actionsDiv);
    return article;
}

/**
 * Render all replies
 */
function renderReplies() {
    replyListContainer.innerHTML = "";
    if (currentReplies.length === 0) {
        replyListContainer.innerHTML = "<p>No replies yet.</p>";
        return;
    }
    currentReplies.forEach(reply => replyListContainer.appendChild(createReplyArticle(reply)));
}

/**
 * Handle adding a new reply
 */
async function handleAddReply(event) {
    event.preventDefault();

    const text = newReplyText.value.trim();
    if (!text) return;

    const newReply = {
        topic_id: currentTopicId,
        text,
        author: 'Student'
    };

    try {
        const response = await fetch(REPLY_API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newReply)
        });
        if (!response.ok) throw new Error('Failed to post reply');

        // Refresh replies after adding
        await fetchReplies(currentTopicId);
        newReplyText.value = "";
    } catch (error) {
        console.error("Error posting reply:", error);
    }
}

/**
 * Handle delete reply button click
 */
async function handleReplyListClick(event) {
    if (event.target.classList.contains('delete-reply-btn')) {
        const replyId = event.target.dataset.id;
        try {
            const response = await fetch(`${REPLY_API_URL}&id=${replyId}`, {
                method: 'DELETE'
            });
            if (!response.ok) throw new Error('Failed to delete reply');

            // Refresh replies after deletion
            await fetchReplies(currentTopicId);
        } catch (error) {
            console.error("Error deleting reply:", error);
        }
    }
}

// --- Initialize Page ---
// Fetch topic and replies, and attach event listeners
async function initializePage() {
    currentTopicId = getTopicIdFromURL();

    if (!currentTopicId) {
        topicSubject.textContent = "Topic not found.";
        return;
    }

    const topic = await fetchTopic(currentTopicId);
    if (!topic) {
        topicSubject.textContent = "Topic not found.";
        return;
    }

    renderOriginalPost(topic);
    await fetchReplies(currentTopicId);

    replyForm.addEventListener('submit', handleAddReply);
    replyListContainer.addEventListener('click', handleReplyListClick);
}

// --- Initial Page Load ---
initializePage();
