// --- Global Data Store ---
let currentTopicId = null;
let currentReplies = []; // Holds replies for this topic

// --- Element Selections ---
const topicSubject = document.querySelector('#topic-subject');
const opMessage = document.querySelector('#op-message');
const opFooter = document.querySelector('#op-footer');
const replyListContainer = document.querySelector('#reply-list-container');
const replyForm = document.querySelector('#reply-form');
const newReplyText = document.querySelector('#new-reply');

// --- Functions ---

function getTopicIdFromURL() {
    const params = new URLSearchParams(window.location.search);
    return params.get('id');
}

function renderOriginalPost(topic) {
    topicSubject.textContent = topic.subject;
    opMessage.textContent = topic.message;
    opFooter.textContent = `Posted by: ${topic.author} on ${topic.date}`;
}

function createReplyArticle(reply) {
    const article = document.createElement('article');

    const p = document.createElement('p');
    p.textContent = reply.text;
    article.appendChild(p);

    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${reply.author} on ${reply.date}`;
    article.appendChild(footer);

    const actionsDiv = document.createElement('div');
    const deleteBtn = document.createElement('a');
    deleteBtn.href = "#";
    deleteBtn.className = "button contrast delete-reply-btn";
    deleteBtn.dataset.id = reply.id;
    deleteBtn.textContent = "Delete";
    actionsDiv.appendChild(deleteBtn);

    article.appendChild(actionsDiv);

    return article;
}

function renderReplies() {
    replyListContainer.innerHTML = "";
    currentReplies.forEach(reply => {
        const article = createReplyArticle(reply);
        replyListContainer.appendChild(article);
    });
}

function handleAddReply(event) {
    event.preventDefault();

    const text = newReplyText.value.trim();
    if (!text) return;

    const newReply = {
        id: `reply_${Date.now()}`,
        author: 'Student',
        date: new Date().toISOString().split('T')[0],
        text
    };

    currentReplies.push(newReply);
    renderReplies();
    newReplyText.value = "";
}

function handleReplyListClick(event) {
    if (event.target.classList.contains('delete-reply-btn')) {
        const id = event.target.dataset.id;
        currentReplies = currentReplies.filter(reply => reply.id !== id);
        renderReplies();
    }
}

async function initializePage() {
    currentTopicId = getTopicIdFromURL();

    if (!currentTopicId) {
        topicSubject.textContent = "Topic not found.";
        return;
    }

    try {
        const [topicsResp, repliesResp] = await Promise.all([
            fetch('topics.json'),
            fetch('replies.json')
        ]);

        const topicsData = topicsResp.ok ? await topicsResp.json() : [];
        const repliesData = repliesResp.ok ? await repliesResp.json() : {};

        const topic = topicsData.find(t => t.id === currentTopicId);
        currentReplies = repliesData[currentTopicId] || [];

        if (topic) {
            renderOriginalPost(topic);
            renderReplies();
            replyForm.addEventListener('submit', handleAddReply);
            replyListContainer.addEventListener('click', handleReplyListClick);
        } else {
            topicSubject.textContent = "Topic not found.";
        }

    } catch (error) {
        console.error("Error loading topic or replies:", error);
        topicSubject.textContent = "Error loading topic.";
    }
}

// --- Initial Page Load ---
initializePage();
