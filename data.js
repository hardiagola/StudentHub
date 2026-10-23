
document.addEventListener("DOMContentLoaded", () => {
  const PAGE_SIZE = 5;

  // Safely fetch and parse external JSON
  async function fetchJSON(file) {
    const response = await fetch(file);

    if (!response.ok) {
      throw new Error(`Unable to load ${file}: ${response.status}`);
    }

    const data = await response.json();

    if (!Array.isArray(data)) {
      throw new Error(`${file} must contain a JSON array.`);
    }

    return data;
  }

  // Create elements safely without inserting untrusted HTML
  function createElement(tag, text, className) {
    const element = document.createElement(tag);
    element.textContent = text;

    if (className) element.className = className;

    return element;
  }

  // Shared search, filter, sort and pagination logic
  function setupDataView(config) {
    const {
      items,
      searchInput,
      filterInput,
      sortInput,
      container,
      status,
      pagination,
      searchText,
      filterText,
      sortItems,
      renderItem
    } = config;

    let currentPage = 1;

    function render() {
      const query = searchInput.value.trim().toLowerCase();
      const category = filterInput ? filterInput.value : "";

      let filtered = items.filter(item => {
        const matchesSearch = searchText(item)
          .toLowerCase()
          .includes(query);

        const matchesCategory = !category ||
          filterText(item) === category;

        return matchesSearch && matchesCategory;
      });

      filtered = sortItems(filtered, sortInput.value);

      const totalPages = Math.max(
        1,
        Math.ceil(filtered.length / PAGE_SIZE)
      );

      currentPage = Math.min(currentPage, totalPages);

      const start = (currentPage - 1) * PAGE_SIZE;
      const pageItems = filtered.slice(start, start + PAGE_SIZE);

      container.replaceChildren();

      if (filtered.length === 0) {
        container.appendChild(
          createElement("p", "No matching records found.")
        );
      } else {
        pageItems.forEach(item => {
          container.appendChild(renderItem(item));
        });
      }

      status.textContent =
        `${filtered.length} record(s) found. Page ${currentPage} of ${totalPages}.`;

      pagination.replaceChildren();

      if (totalPages > 1) {
        const previous = createElement("button", "Previous");
        previous.type = "button";
        previous.disabled = currentPage === 1;
        previous.addEventListener("click", () => {
          currentPage--;
          render();
        });

        const next = createElement("button", "Next");
        next.type = "button";
        next.disabled = currentPage === totalPages;
        next.addEventListener("click", () => {
          currentPage++;
          render();
        });

        const pageLabel = createElement(
          "span",
          ` Page ${currentPage} of ${totalPages} `
        );

        pagination.append(previous, pageLabel, next);
      }
    }

    searchInput.addEventListener("input", () => {
      currentPage = 1;
      render();
    });

    if (filterInput) {
      filterInput.addEventListener("change", () => {
        currentPage = 1;
        render();
      });
    }

    sortInput.addEventListener("change", () => {
      currentPage = 1;
      render();
    });

    render();
  }

  function showError(status, error) {
    status.textContent =
      "Unable to load data. Please run the website using a local server and check the JSON filenames.";

    console.error(error);
  }

  // EVENTS PAGE
  const eventsList = document.getElementById("eventsList");

  if (eventsList) {
    const status = document.getElementById("eventsStatus");

    fetchJSON("events.json")
      .then(events => {
        setupDataView({
          items: events,
          searchInput: document.getElementById("eventSearch"),
          filterInput: document.getElementById("eventCategory"),
          sortInput: document.getElementById("eventSort"),
          container: eventsList,
          status,
          pagination: document.getElementById("eventsPagination"),

          searchText: event =>
            `${event.name} ${event.venue} ${event.category}`,

          filterText: event => event.category,

          sortItems: (items, sort) => {
            const sorted = [...items];

            sorted.sort((a, b) => {
              if (sort === "date-asc") {
                return a.date.localeCompare(b.date);
              }

              if (sort === "date-desc") {
                return b.date.localeCompare(a.date);
              }

              const result = a.name.localeCompare(b.name);
              return sort === "name-desc" ? -result : result;
            });

            return sorted;
          },

          renderItem: event => {
            const card = createElement("article", "", "data-card");

            card.appendChild(createElement("h3", event.name));
            card.appendChild(createElement("p", `Date: ${event.date}`));
            card.appendChild(createElement("p", `Venue: ${event.venue}`));
            card.appendChild(createElement("p", `Category: ${event.category}`));

            return card;
          }
        });
      })
      .catch(error => showError(status, error));
  }


  // STUDENTS / ADMIN PAGE
  const studentsList = document.getElementById("studentsList");

  if (studentsList) {
    const status = document.getElementById("studentsStatus");

    fetchJSON("students.json")
      .then(students => {
        setupDataView({
          items: students,
          searchInput: document.getElementById("studentSearch"),
          filterInput: document.getElementById("studentCourse"),
          sortInput: document.getElementById("studentSort"),
          container: studentsList,
          status,
          pagination: document.getElementById("studentsPagination"),

          searchText: student =>
            `${student.name} ${student.enrollment} ${student.course}`,

          filterText: student => student.course,

          sortItems: (items, sort) => {
            const sorted = [...items];

            sorted.sort((a, b) => {
              if (sort === "year-asc") return a.year - b.year;
              if (sort === "year-desc") return b.year - a.year;

              const result = a.name.localeCompare(b.name);
              return sort === "name-desc" ? -result : result;
            });

            return sorted;
          },

          renderItem: student => {
            const row = document.createElement("tr");

            [
              student.name,
              student.enrollment,
              student.course,
              `Year ${student.year}`
            ].forEach(value => {
              row.appendChild(createElement("td", String(value)));
            });

            return row;
          }
        });
      })
      .catch(error => showError(status, error));
  }


  // FAQ PAGE
  const faqsList = document.getElementById("faqsList");

  if (faqsList) {
    const status = document.getElementById("faqsStatus");

    fetchJSON("faqs.json")
      .then(faqs => {
        setupDataView({
          items: faqs,
          searchInput: document.getElementById("faqSearch"),
          filterInput: document.getElementById("faqCategory"),
          sortInput: document.getElementById("faqSort"),
          container: faqsList,
          status,
          pagination: document.getElementById("faqsPagination"),

          searchText: faq => `${faq.question} ${faq.answer}`,

          filterText: faq => faq.category,

          sortItems: (items, sort) => {
            const sorted = [...items];

            if (sort === "question-asc") {
              sorted.sort((a, b) =>
                a.question.localeCompare(b.question)
              );
            } else if (sort === "question-desc") {
              sorted.sort((a, b) =>
                b.question.localeCompare(a.question)
              );
            } else {
              sorted.sort((a, b) => a.id - b.id);
            }

            return sorted;
          },

          renderItem: faq => {
            const article = createElement("article", "", "faq-item");
            const question = createElement(
              "button",
              faq.question,
              "faq-question"
            );

            question.type = "button";
            question.setAttribute("aria-expanded", "false");

            const answer = createElement("div", "", "faq-answer");
            answer.hidden = true;
            answer.appendChild(createElement("p", faq.answer));
            answer.appendChild(
              createElement("small", `Category: ${faq.category}`)
            );

            question.addEventListener("click", () => {
              const isOpen =
                question.getAttribute("aria-expanded") === "true";

              question.setAttribute("aria-expanded", String(!isOpen));
              answer.hidden = isOpen;
            });

            article.append(question, answer);
            return article;
          }
        });
      })
      .catch(error => showError(status, error));
  }
});
