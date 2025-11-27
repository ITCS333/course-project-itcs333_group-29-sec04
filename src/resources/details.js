/*
  Requirement: Populate the resource detail page and discussion forum.

  Instructions:
  1. Link this file to `details.html` using:
     <script src="details.js" defer></script>

  2. In `details.html`, add the following IDs:
     - To the <h1>: `id="resource-title"`
     - To the description <p>: `id="resource-description"`
     - To the "Access Resource Material" <a> tag: `id="resource-link"`
     - To the <div> for comments: `id="comment-list"`
     - To the "Leave a Comment" <form>: `id="comment-form"`
     - To the <textarea>: `id="new-comment"`

  3. Implement the TODOs below.
*/

// --- Global Data Store ---
// These will hold the data related to *this* resource.
let currentResourceId = null;
let currentComments = [];

// --- Element Selections ---
// TODO: Select all the elements you added IDs for in step 2.
const resourceTitle = document.getElementById("resource-title");
const description = document.getElementById("resource-description");
const resourceLink = document.getElementById("resource-link");
const commentList = document.getElementById("comment-list");
const commentForm = document.getElementById("comment-form");
const newComment = document.getElementById("new-comment");

const RESOURCE_URL = "./api/index.php?resource=resources";
const COMMENTS_URL = "./api/index.php?resource=comments";
// --- Functions ---

/**
 * TODO: Implement the getResourceIdFromURL function.
 * It should:
 * 1. Get the query string from `window.location.search`.
 * 2. Use the `URLSearchParams` object to get the value of the 'id' parameter.
 * 3. Return the id.
 */
function getResourceIdFromURL() {
  // ... your implementation here ...
  const queryString = window.location.search;
  const params = new URLSearchParams(queryString);
  const id = params.get('id');
  return id;
}

/**
 * TODO: Implement the renderResourceDetails function.
 * It takes one resource object.
 * It should:
 * 1. Set the `textContent` of `resourceTitle` to the resource's title.
 * 2. Set the `textContent` of `resourceDescription` to the resource's description.
 * 3. Set the `href` attribute of `resourceLink` to the resource's link.
 */
function renderResourceDetails(resource) {
  // ... your implementation here ...
  resourceTitle.textContent = resource.title;
  description.textContent = resource.description;
  resourceLink.href = resource.link;
}

/**
 * TODO: Implement the createCommentArticle function.
 * It takes one comment object {author, text}.
 * It should return an <article> element matching the structure in `details.html`.
 * (e.g., an <article> containing a <p> and a <footer>).
 */
function createCommentArticle(comment) {
  // ... your implementation here ...
  const article = document.createElement("article");
  const paragraph = document.createElement("p");
  const footer = document.createElement("footer");
  const deletebtn = document.createElement("button");
  deletebtn.classList.add("comment-delete-btn");
  deletebtn.textContent = "×";
  paragraph.textContent = comment.text;
  footer.textContent = comment.author;
  deletebtn.dataset.id = comment.id;
  article.appendChild(paragraph);
  article.appendChild(footer);
  article.appendChild(deletebtn);
  article.classList.add("comment-item");

  return article;
}

/**
 * TODO: Implement the renderComments function.
 * It should:
 * 1. Clear the `commentList`.
 * 2. Loop through the global `currentComments` array.
 * 3. For each comment, call `createCommentArticle()`, and
 * append the resulting <article> to `commentList`.
 */
function renderComments() {
  // ... your implementation here ...
  commentList.innerHTML = "";
  currentComments.forEach(comment => {
    const createdArticle = createCommentArticle(comment);
    commentList.appendChild(createdArticle);
  });
}

/**
 * TODO: Implement the handleAddComment function.
 * This is the event handler for the `commentForm` 'submit' event.
 * It should:
 * 1. Prevent the form's default submission.
 * 2. Get the text from `newComment.value`.
 * 3. If the text is empty, return.
 * 4. Create a new comment object: { author: 'Student', text: commentText }
 * (For this exercise, 'Student' is a fine hardcoded author).
 * 5. Add the new comment to the global `currentComments` array (in-memory only).
 * 6. Call `renderComments()` to refresh the list.
 * 7. Clear the `newComment` textarea.
 */
function handleAddComment(event) {
  // ... your implementation here ...
  event.preventDefault();
  const comment = newComment.value;
  if (comment === "") return;
  const rid = getResourceIdFromURL();
  const commentObject = { id: "", resource_id: rid, author: "Student", text: comment };

  fetch(COMMENTS_URL + "&action=comment", {
    method: "POST",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(commentObject)
  })
    .then(res => res.json())
    .then(result => {
      if (result.success) {
        commentObject.id = result.id;
        currentComments.push(commentObject);
        renderComments();
        commentForm.reset();
      }
      else console.error("Comments generation failed:", result.message);
    });
}

function handleDeleteComment(event) {
  if (event.target.classList.contains("comment-delete-btn")) {
    const data = Number(event.target.dataset.id);
    fetch(`${COMMENTS_URL}&action=delete_comment&id=${data}`, {
      method: "DELETE"
    })
      .then(res => res.json())
      .then(result => {
        if (result.success) {
          currentComments = currentComments.filter(d => Number(d.id) !== data);
          renderComments();
        }
        else { console.error("Delete failed:", result.message); }
      })

  }
}


/**
 * TODO: Implement an `initializePage` function.
 * This function needs to be 'async'.
 * It should:
 * 1. Get the `currentResourceId` by calling `getResourceIdFromURL()`.
 * 2. If no ID is found, set `resourceTitle.textContent = "Resource not found."` and stop.
 * 3. `fetch` both 'resources.json' and 'resource-comments.json' (you can use `Promise.all`).
 * 4. Parse both JSON responses.
 * 5. Find the correct resource from the resources array using the `currentResourceId`.
 * 6. Get the correct comments array from the comments object using the `currentResourceId`.
 * Store this in the global `currentComments` variable. (If no comments exist, use an empty array).
 * 7. If the resource is found:
 * - Call `renderResourceDetails()` with the resource object.
 * - Call `renderComments()` to show the initial comments.
 * - Add the 'submit' event listener to `commentForm` (calls `handleAddComment`).
 * 8. If the resource is not found, display an error in `resourceTitle`.
 */
async function initializePage() {
  // ... your implementation here ...
  currentResourceId = getResourceIdFromURL();
  if (!currentResourceId) {
    resourceTitle.textContent = "Resource not found";
    return;
  }
  const resourceResponse = await fetch(RESOURCE_URL + "&id=" + currentResourceId);
  const resource = await resourceResponse.json();
  const commentResponse = await fetch(COMMENTS_URL + "&action=comments&resource_id=" + currentResourceId);
  const comment = await commentResponse.json();
  const findResource = resource.data;
  console.log(comment.data);

  // let findResource = resource.find(r => r.id === currentResourceId);

  if (findResource) {
    currentComments = comment.data || [];
    renderResourceDetails(findResource);
    renderComments();
    commentForm.addEventListener("submit", handleAddComment);
    commentList.addEventListener("click", handleDeleteComment);
  }
  else { resourceTitle.textContent = "error"; }

}

// --- Initial Page Load ---
initializePage();
