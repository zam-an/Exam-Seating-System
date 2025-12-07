
[projectworking.webm](https://github.com/user-attachments/assets/45838879-4c47-4eb6-af00-850c037dd07d)

```markdown
# Exam Seating System

The Exam Seating System is a web-based application to automate and manage allocation of exam seats to students. It helps create and visualize seating plans, manage students, rooms, departments, and semesters, and export seating arrangements for exam sessions.

This README focuses on how to run the project locally using XAMPP.

## Key Features
- Dashboard overview with statistics and summaries.
- Student management: import students via CSV, view/add/edit student records.
- Room and seat allocation: create rooms and assign students to seats.
- Seating plans visualization and export.
- Department and semester management.
- Simple user signup UI.
- Frontend structured for extension with backend API endpoints in `/api`.

## Repository structure (what I found)
- Web engineering project/
  - index.html — Landing / public entry
  - dashboard.html — Admin dashboard
  - students.html — Student management page
  - students_sample.csv — Example CSV for importing students
  - rooms.html — Room management
  - seatings.html — Seating visualization
  - plans.html — Exam plan details
  - departments.html — Department management
  - semesters.html — Semester management
  - signup.html — User registration
  - assets/, styles/, scripts/ — Static assets, stylesheets, and JavaScript
- api/ — Backend API endpoints (prepared for extensibility)

> Note: The project folder currently contains a top-level folder named "Web engineering project" (with spaces). When running on XAMPP it's simpler to remove or rename spaces to avoid URL-encoding issues.

## Prerequisites
- XAMPP installed (Apache + PHP). MySQL (MariaDB) if you want to use a backend or persistent storage.
- A modern browser (Chrome, Firefox, Edge).
- Optional: a text editor for editing configuration files (VS Code, Sublime, etc.)

## Local setup with XAMPP (Windows / macOS / Linux)
1. Clone the repository:
   ```bash
   git clone https://github.com/zam-an/Exam-Seating-System.git
   ```

2. Locate the frontend site folder
   - The interactive front-end is inside the "Web engineering project" directory.
   - For simplicity, rename the folder to remove spaces:
     - From: `Web engineering project`
     - To: `exam-seating-system`

3. Move the folder into XAMPP's document root:
   - Windows (default):
     - Move `exam-seating-system` to `C:\xampp\htdocs\`
   - macOS (MAMP or XAMPP):
     - Move to `/Applications/XAMPP/htdocs/` or MAMP htdocs
   - Linux:
     - Move to `/opt/lampp/htdocs/`

4. Start XAMPP (Control Panel) and enable:
   - Apache — required
   - MySQL (MariaDB) — optional if you use a backend API or persistent DB

5. Open the app in your browser:
   - If you renamed the folder to `exam-seating-system`:
     ```
     http://localhost/exam-seating-system/index.html
     ```
   - If you kept the original name with spaces, the URL will be URL-encoded (not recommended):
     ```
     http://localhost/Web%20engineering%20project/index.html
     ```

## Using the sample CSV (students_sample.csv)
- The front-end includes a sample: `Web engineering project/students_sample.csv`
- On the Students page, there is an import option (if implemented) — select the CSV to bulk load student data.
- If the import is client-side JavaScript, the upload will be processed locally in the browser. If it posts to an API, you must enable/configure the backend to receive and process the CSV.

## Notes about backend/API
- There is an `api/` directory intended for backend endpoints. If you want a persistent backend:
  1. Create a MySQL database, e.g., `exam_seating`.
  2. Create required tables (students, rooms, seatings, departments, semesters, users).
  3. Add a configuration file for DB connection (example: `api/config.php` or `api/.env`) and update connection settings (host, username, password, database).
  4. Update front-end API URLs (in `scripts/`) to point to the correct API endpoints (e.g., `http://localhost/exam-seating-system/api/...`).

- If no backend is present yet, the UI will function as a static prototype using front-end logic only.

## Common troubleshooting
- 404 when opening index.html:
  - Ensure folder is inside XAMPP's htdocs folder and that Apache is running.
  - Avoid folder names with spaces — rename the folder to a simple name like `exam-seating-system`.
- MySQL connection issues:
  - Start MySQL in XAMPP and confirm login credentials in your API config match phpMyAdmin.
- Ports in use:
  - If Apache won't start, check port conflicts (often Skype or other services). You can change Apache ports in XAMPP if needed.
- Permissions:
  - On macOS/Linux you may need sudo when moving files to /opt/lampp/htdocs.
- CSV import not working:
  - Check browser console for JavaScript errors, and verify whether import is implemented client-side or expects an API.

## Recommended next steps
- If you want full persistence, add a simple backend (PHP or Node) in `api/` with endpoints for students, rooms, seatings, departments, and authentication.
- Create an SQL schema and a sample seed script to import `students_sample.csv` into the database.
- Add instructions and example config (e.g., `api/config.example.php`) so users can easily plug in DB credentials.

## Contributing
- Contributions are welcome. Please open issues or PRs to:
  - Add backend endpoints and DB schema
  - Improve UI/UX
  - Add tests and sample data

## License
- Add your chosen license (e.g., MIT) in a LICENSE file if you plan to share this project publicly.

---

If you'd like, I can:
- produce a ready-to-copy SQL schema and a sample `api/config.example.php` for XAMPP/PHP + MySQL,
- rename and reorganize the frontend folder to remove spaces and update relative paths,
- or create a minimal PHP backend (API endpoints) to accept CSV imports and persist students to a MySQL database.
```
