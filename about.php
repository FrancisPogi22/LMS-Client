<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f9f9f9;
        }
        header {
            background-color: #007bff;
            color: white;
            padding: 20px 0;
            text-align: center;
        }
        header h1 {
            margin: 0;
            font-size: 2.5em;
        }
        section {
            padding: 40px 20px;
            max-width: 1200px;
            margin: 0 auto;
            background-color: #fff;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        h2 {
            color: #333;
            margin-bottom: 20px;
            text-align: center;
            font-size: 2em;
        }
        p {
            color: #666;
            line-height: 1.6;
            margin: 0 0 20px;
        }
        .mission, .vision, .values {
            margin-bottom: 40px;
        }
        .team {
            display: flex;
            flex-wrap: wrap;
            justify-content: center;
        }
        .team-member {
            background-color: #f0f0f0;
            border-radius: 10px;
            margin: 10px;
            padding: 20px;
            text-align: center;
            width: 200px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.2);
        }
        .team-member img {
            width: 100%;
            border-radius: 50%;
            margin-bottom: 15px;
        }
        footer {
            background-color: #007bff;
            color: white;
            text-align: center;
            padding: 20px 0;
            position: relative;
            bottom: 0;
            width: 100%;
        }
        @media (max-width: 600px) {
            .team {
                flex-direction: column;
                align-items: center;
            }
        }
    </style>
</head>
<body>

<header>
    <h1>About Us</h1>
</header>

<section>
    <div class="mission">
        <h2>Our Mission</h2>
        <p>To provide quality education and foster a learning environment that inspires students to achieve their full potential.</p>
    </div>

    <div class="vision">
        <h2>Our Vision</h2>
        <p>To be a leading institution in education, preparing students for a successful future through innovative and effective teaching methodologies.</p>
    </div>

    <div class="values">
        <h2>Our Values</h2>
        <p>Integrity, Excellence, Innovation, Collaboration, and Respect.</p>
    </div>

    <div class="team">
        <h2>Meet Our Team</h2>
        <div class="team-member">
            <img src="https://via.placeholder.com/150" alt="Team Member 1">
            <h3>Garry Zaldy Deguzman</h3>
            <p>CEO</p>
        </div>
        <div class="team-member">
            <img src="https://via.placeholder.com/150" alt="Team Member 2">
            <h3>John Smith</h3>
            <p>CTO</p>
        </div>
        <div class="team-member">
            <img src="https://via.placeholder.com/150" alt="Team Member 3">
            <h3>Emily Johnson</h3>
            <p>CMO</p>
        </div>
        <div class="team-member">
            <img src="https://via.placeholder.com/150" alt="Team Member 4">
            <h3>Michael Brown</h3>
            <p>CFO</p>
        </div>
    </div>
</section>

<footer>
    <p>&copy; 2024 Your Organization. All rights reserved.</p>
</footer>

</body>
</html>
