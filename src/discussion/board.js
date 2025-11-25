// --- Global Data Store ---
let topics = [];

// --- Element Selections ---
const newTopicForm = document.querySelector('#new-topic-form');
const topicListContainer = document.querySelector('#topic-list-container');

// --- Functions ---

function createTopicArticle(topic) {
    const article = document.createElement('article');

    // Title with link
    const h3 = document.createElement('h3');
    const link = document.createElement('a');
    link.href = `topic.html?id=${topic.id}`;
    link.textContent = topic.subject;
    h3.appendChild(link);
    article.appendChild(h3);

    // Footer with author and date
    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${topic.author} on ${topic.date}`;
    article.appendChild(footer);

    // Action buttons
    const actionsDiv = document.createElement('div');

    const editBtn = document.createElement('a');
    editBtn.href = "#";
    editBtn.className = "button secondary";
    editBtn.textContent = "Edit";

    const deleteBtn = document.createElement('a');
    deleteBtn.href = "#";
    deleteBtn.className = "button contrast delete-btn";
    deleteBtn.dataset.id = topic.id;
    deleteBtn.textContent = "Delete";

    actionsDiv.appendChild(editBtn);
    actionsDiv.appendChild(deleteBtn);

    article.appendChild(actionsDiv);

    return article;
}

function renderTopics() {
    topicListContainer.innerHTML = "";
    topics.forEach(topic => {
        const article = createTopicArticle(topic);
        topicListContainer.appendChild(article);
    });
}

function handleCreateTopic(event) {
    event.preventDefault();

    const subject = document.querySelector('#topic-subject').value.trim();
    const message = document.querySelector('#topic-message').value.trim();

    if (!subject || !message) return;

    const newTopic = {
        id: `topic_${Date.now()}`,
        subject,
        message,
        author: "Student",
        date: new Date().toISOString().split('T')[0]
    };

    topics.push(newTopic);
    renderTopics();
    newTopicForm.reset();
}

function handleTopicListClick(event) {
    if (event.target.classList.contains('delete-btn')) {
        const id = event.target.dataset.id;
        topics = topics.filter(topic => topic.id !== id);
        renderTopics();
    }
}

async function loadAndInitialize() {
    try {
        const response = await fetch('topics.json');
        if (response.ok) {
            const data = await response.json();
            topics = data;
        }
    } catch (error) {
        console.warn("Could not load topics.json, using empty array.");
    }

    renderTopics();

    newTopicForm.addEventListener('submit', handleCreateTopic);
    topicListContainer.addEventListener('click', handleTopicListClick);
}

// --- Initial Page Load ---
loadAndInitialize();
