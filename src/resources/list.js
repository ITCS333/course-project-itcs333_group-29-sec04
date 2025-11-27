/*
  Requirement: Populate the "Course Resources" list page.

  Instructions:
  1. Link this file to `list.html` using:
     <script src="list.js" defer></script>

  2. In `list.html`, add an `id="resource-list-section"` to the
     <section> element that will contain the resource articles.

  3. Implement the TODOs below.
*/

// --- Element Selections ---
// TODO: Select the section for the resource list ('#resource-list-section').
const listSection = document.querySelector("#resource-list-section");
const RESOURCE_URL = "./api/index.php?resource=resources";
// --- Functions ---
/**
 * TODO: Implement the createResourceArticle function.
 * It takes one resource object {id, title, description}.
 * It should return an <article> element matching the structure in `list.html`.
 * The "View Resource & Discussion" link's `href` MUST be set to `details.html?id=${id}`.
 * (This is how the detail page will know which resource to load).
 */
function createResourceArticle(resource) {
  // ... your implementation here ...
  const article = document.createElement("article");
  const heading = document.createElement("h2");
  const description = document.createElement("p");
  const link = document.createElement("a");
  heading.textContent = resource.title;
  description.textContent = resource.description;
  link.textContent = "View Resource & Discussion";
  link.href = `details.html?id=${resource.id}`;
  article.appendChild(heading);
  article.appendChild(description);
  article.appendChild(link);
  return article;
}


/**
 * TODO: Implement the loadResources function.
 * This function needs to be 'async'.
 * It should:
 * 1. Use `fetch()` to get data from 'resources.json'.
 * 2. Parse the JSON response into an array.
 * 3. Clear any existing content from `listSection`.
 * 4. Loop through the resources array. For each resource:
 * - Call `createResourceArticle()`.
 * - Append the returned <article> element to `listSection`.
 */
async function loadResources() {
  // ... your implementation here ...
  const response = await fetch(RESOURCE_URL);
  if (!response.ok) {
    throw new Error("Could not fetch resources");
  }
  const resource = await response.json();
  if (!resource.success) {
    throw new Error(resource.error || "API returned an error");
  }
  const resourcesData = resource.data || [];

  listSection.innerHTML = "";
  resourcesData.forEach(e => {
    const article = createResourceArticle(e);
    listSection.appendChild(article);
  });

}

// --- Initial Page Load ---
// Call the function to populate the page.
loadResources();
