// --- Global State ---
let topics = [];

// --- API URL ---
const API_URL = './api/index.php?resource=topics';

// --- Element Selections ---
const newTopicForm = document.querySelector('#new-topic-form');
const topicListContainer = document.querySelector('#topic-list-container');
const searchInput = document.getElementById("search-input");
const filterSelect = document.getElementById("filter-select");
const orderBtn = document.getElementById("order-btn");
let sortAsc = true;
let timer;

// --- Helper Functions ---

/**
 * Fetch topics from API with optional search, sorting, and order
 */
async function fetchTopics(search = '', sort = 'created_at', order = 'desc') {
    try {
        const url = `${API_URL}&search=${encodeURIComponent(search)}&sort=${sort}&order=${order}`;
        const response = await fetch(url);
        if (!response.ok) throw new Error('Failed to fetch topics');
        const result = await response.json();
        topics = result.data || [];
        renderTopics();
    } catch (error) {
        console.error("Error fetching topics:", error);
        topicListContainer.innerHTML = "<p>Error loading topics.</p>";
    }
}

/**
 * Create single topic article element
 */
function createTopicArticle(topic) {
    const article = document.createElement('article');

    // Topic title with link
    const h3 = document.createElement('h3');
    const link = document.createElement('a');
    link.href = `topic.html?id=${topic.id}`;
    link.textContent = topic.subject;
    h3.appendChild(link);
    article.appendChild(h3);

    // Footer info
    const footer = document.createElement('footer');
    footer.textContent = `Posted by: ${topic.author} on ${topic.created_at}`;
    article.appendChild(footer);

    // Actions (Edit/Delete)
    const actionsDiv = document.createElement('div');
    const deleteBtn = document.createElement('button');
    deleteBtn.textContent = "Delete";
    deleteBtn.className = "contrast delete-btn";
    deleteBtn.dataset.id = topic.id;
    actionsDiv.appendChild(deleteBtn);

    article.appendChild(actionsDiv);
    return article;
}

/**
 * Render all topics in the container
 */
function renderTopics() {
    topicListContainer.innerHTML = "";
    if (topics.length === 0) {
        topicListContainer.innerHTML = "<p>No topics found.</p>";
        return;
    }
    topics.forEach(topic => topicListContainer.appendChild(createTopicArticle(topic)));
}

/**
 * Handle form submission to create new topic
 */
async function handleCreateTopic(event) {
    event.preventDefault();
    const subject = document.querySelector('#topic-subject').value.trim();
    const message = document.querySelector('#topic-message').value.trim();
    if (!subject || !message) return;

    const newTopic = { subject, message, author: "Student" };

    try {
        const response = await fetch(API_URL, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(newTopic)
        });
        if (!response.ok) throw new Error('Failed to create topic');
        fetchTopics();
        newTopicForm.reset();
    } catch (error) {
        console.error("Error creating topic:", error);
    }
}

/**
 * Handle clicks in topic list (delete button)
 */
async function handleTopicListClick(event) {
    if (event.target.classList.contains('delete-btn')) {
        const topicId = event.target.dataset.id;
        try {
            const response = await fetch(`${API_URL}&id=${topicId}`, { method: 'DELETE' });
            if (!response.ok) throw new Error('Failed to delete topic');
            fetchTopics();
        } catch (error) {
            console.error("Error deleting topic:", error);
        }
    }
}

/**
 * Load topics based on search/filter/order
 */
function loadFilteredTopics() {
    const search = searchInput.value.trim();
    let sort = filterSelect.value;
    const order = sortAsc ? "asc" : "desc";
    fetchTopics(search, sort, order);
}

// --- Initialize App ---
async function loadAndInitialize() {
    // Load topics first
    await fetchTopics();}

// --- Event Listeners ---
if (newTopicForm) newTopicForm.addEventListener('submit', handleCreateTopic);
if (topicListContainer) topicListContainer.addEventListener('click', handleTopicListClick);
if (searchInput) {
    searchInput.addEventListener("input", () => {
        clearTimeout(timer);
        timer = setTimeout(loadFilteredTopics, 300);
    });
}
if (filterSelect) filterSelect.addEventListener("change", loadFilteredTopics);
if (orderBtn) {
    orderBtn.addEventListener("click", () => {
        sortAsc = !sortAsc;
        orderBtn.textContent = sortAsc ? "Asc" : "Desc";
        loadFilteredTopics();
    });
}

// --- Initial Load ---
loadAndInitialize();
