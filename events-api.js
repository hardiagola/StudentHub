
document.addEventListener("DOMContentLoaded", () => {
    const apiUrl = "api/events.php";

    const form = document.getElementById("eventForm");
    const eventId = document.getElementById("eventId");
    const eventName = document.getElementById("eventName");
    const eventDate = document.getElementById("eventDate");
    const eventVenue = document.getElementById("eventVenue");
    const eventCategory = document.getElementById("eventCategory");
    const eventDescription = document.getElementById("eventDescription");

    const tableBody = document.getElementById("eventsTableBody");
    const messageBox = document.getElementById("apiMessage");
    const loadingMessage = document.getElementById("loadingMessage");
    const saveButton = document.getElementById("saveButton");
    const cancelButton = document.getElementById("cancelButton");
    const refreshButton = document.getElementById("refreshButton");

    let events = [];

    function showMessage(message, type = "success") {
        messageBox.textContent = message;
        messageBox.className = `api-message ${type}`;
        messageBox.hidden = false;
    }

    function hideMessage() {
        messageBox.hidden = true;
    }

    async function requestApi(url, options = {}) {
        const response = await fetch(url, {
            credentials: "same-origin",
            ...options,
            headers: {
                ...(options.body ? { "Content-Type": "application/json" } : {}),
                ...options.headers
            }
        });

        let result;

        try {
            result = await response.json();
        } catch {
            throw new Error("The server returned an invalid JSON response.");
        }

        if (!response.ok || result.success !== true) {
            throw new Error(result.message || `Request failed (${response.status}).`);
        }

        return result;
    }

    function makeCell(value) {
        const cell = document.createElement("td");
        cell.textContent = value ?? "";
        return cell;
    }

    function makeButton(label, action, id) {
        const button = document.createElement("button");
        button.type = "button";
        button.textContent = label;
        button.addEventListener("click", () => action(id));
        return button;
    }

    function renderEvents() {
        tableBody.replaceChildren();

        if (events.length === 0) {
            const row = document.createElement("tr");
            const cell = makeCell("No events found.");
            cell.colSpan = 7;
            row.appendChild(cell);
            tableBody.appendChild(row);
            return;
        }

        events.forEach((event) => {
            const row = document.createElement("tr");

            row.appendChild(makeCell(event.event_id));
            row.appendChild(makeCell(event.event_name));
            row.appendChild(makeCell(event.event_date));
            row.appendChild(makeCell(event.venue));
            row.appendChild(makeCell(event.category));
            row.appendChild(makeCell(event.description));

            const actions = document.createElement("td");
            actions.appendChild(
                makeButton("Edit", editEvent, event.event_id)
            );
            actions.appendChild(
                makeButton("Delete", deleteEvent, event.event_id)
            );

            row.appendChild(actions);
            tableBody.appendChild(row);
        });
    }

    async function loadEvents() {
        loadingMessage.hidden = false;
        loadingMessage.textContent = "Loading events...";

        try {
            const result = await requestApi(apiUrl);
            events = result.events;
            renderEvents();
            loadingMessage.hidden = true;
        } catch (error) {
            loadingMessage.textContent = "Could not load events.";
            showMessage(error.message, "error");
        }
    }

    function resetForm() {
        form.reset();
        eventId.value = "";
        saveButton.textContent = "Add Event";
        cancelButton.hidden = true;
    }

    function editEvent(id) {
        const event = events.find(
            (item) => Number(item.event_id) === Number(id)
        );

        if (!event) {
            showMessage("Event not found.", "error");
            return;
        }

        eventId.value = event.event_id;
        eventName.value = event.event_name;
        eventDate.value = event.event_date;
        eventVenue.value = event.venue;
        eventCategory.value = event.category;
        eventDescription.value = event.description ?? "";

        saveButton.textContent = "Update Event";
        cancelButton.hidden = false;
        eventName.focus();
    }

    async function deleteEvent(id) {
        if (!confirm("Are you sure you want to delete this event?")) {
            return;
        }

        hideMessage();

        try {
            const result = await requestApi(apiUrl, {
                method: "POST",
                body: JSON.stringify({
                    action: "delete",
                    event_id: id
                })
            });

            showMessage(result.message);
            await loadEvents();
        } catch (error) {
            showMessage(error.message, "error");
        }
    }

    form.addEventListener("submit", async (event) => {
        event.preventDefault();
        hideMessage();

        const editing = eventId.value !== "";

        const payload = {
            action: editing ? "update" : "add",
            event_name: eventName.value.trim(),
            event_date: eventDate.value,
            venue: eventVenue.value.trim(),
            category: eventCategory.value.trim(),
            description: eventDescription.value.trim()
        };

        if (editing) {
            payload.event_id = Number(eventId.value);
        }

        saveButton.disabled = true;

        try {
            const result = await requestApi(apiUrl, {
                method: "POST",
                body: JSON.stringify(payload)
            });

            showMessage(result.message);
            resetForm();
            await loadEvents();
        } catch (error) {
            showMessage(error.message, "error");
        } finally {
            saveButton.disabled = false;
        }
    });

    cancelButton.addEventListener("click", resetForm);
    refreshButton.addEventListener("click", loadEvents);

    loadEvents();
});
