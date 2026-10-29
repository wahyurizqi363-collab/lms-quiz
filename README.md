# LMS Quiz

An online quiz platform for lecturers and students. The timer runs on the server, so closing the tab or changing the clock does not give extra time.

Built with **PHP, MySQL and vanilla JavaScript**. No framework.

## Features

**For students**
- Take timed quizzes with a question navigator and progress bar.
- Question order is shuffled for each student.
- Answers are saved automatically as drafts, so nothing is lost if the page closes.
- A warning appears if the student left the quiz page while the clock kept running.
- See score, rank among classmates, and a review with explanations once the lecturer opens it.

**For lecturers**
- Create quizzes and manage question banks (an Excel template is included).
- Monitor students while a quiz is running.
- View results sorted by score, then fastest time.
- Manage student accounts and open or close the answer review.

## How the timer works

When a student starts a quiz, the server records the start time. On submit, the server compares it with the quiz duration. If time has run out and no answer was sent, the system finalizes the score from the last saved draft.

## Security

- CSRF token on every form.
- PDO prepared statements.
- Role-based access: student and lecturer pages are separate.
- Lecturers register with a private code defined in `config.php`.

## Run it locally (XAMPP)

1. Copy this folder into `htdocs` and start **Apache** and **MySQL**.
2. Create a database named `lms_quiz` and import `schema.sql`.
3. Copy `config.example.php` to `config.php` and fill in your database details and a new `KODE_DOSEN`.
4. Open `http://localhost/lms-quiz/`.

## Author

Muhammad Wahyu Rizqi Pratama, wahyurizqi363@gmail.com
