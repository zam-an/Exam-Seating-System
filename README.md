[projectworking.webm](https://github.com/user-attachments/assets/45838879-4c47-4eb6-af00-850c037dd07d)
Exam Seating System
The Exam Seating System is a web-based application designed to automate and manage the process of allocating seats to students for examinations. It streamlines the organization of exam venues, student assignments, room plans, departments, and related resources, providing a centralized dashboard for administrative users.

Features
Dashboard Overview: Quick access to statistics and summary of seating plans and assignments.
Student Management: Import student data (CSV supported), view, add, and manage student details.
Room Allocation: Create and manage exam rooms, assign students to specific rooms and seats.
Seating Plans: Visualize and export seating arrangements for each exam session.
Department Management: Handle multiple academic departments and their corresponding students and rooms.
Semester Tracking: Organize seatings and schedules by semester.
User Signup: Simple registration interface for new users.
File Structure
Web engineering project/index.html – Landing page.
Web engineering project/dashboard.html – Central dashboard for admins.
Web engineering project/students.html – Student management interface.
Web engineering project/students_sample.csv – Example student data import.
Web engineering project/rooms.html – Room management.
Web engineering project/seatings.html – Seating plan visualization.
Web engineering project/plans.html – Exam plan details.
Web engineering project/departments.html, semesters.html – Manage departments and semesters.
Web engineering project/signup.html – User registration.
Web engineering project/assets/, styles/, scripts/ – Static assets (images, CSS, JS).
api/ – Backend API endpoints (structure for extensibility).
Getting Started
Prerequisites
XAMPP installed for local server environment.
Setup & Running
Clone the repository:

git clone https://github.com/zam-an/Exam-Seating-system.git
Move the project folder (especially Web engineering project) to the htdocs directory in your XAMPP installation.

Start Apache (and optionally MySQL, if you plan backend integration) from the XAMPP Control Panel.

Open your browser and go to:

http://localhost/Web%20engineering%20project/index.html
(Optionally) Import students via the provided CSV sample on the students page.

Technologies Used
HTML, CSS, JavaScript (front-end)
Additional scripts or frameworks can be added in the /scripts folder.
Designed for extensibility with REST API endpoints in /api.
XAMPP for local web server hosting.
Example
The system allows admins to visualize student seatings per room and semester, manage student data efficiently, and ensure proper allocation and tracking of exam resources.

