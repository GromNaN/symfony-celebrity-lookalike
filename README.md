# Symfony/Doctrine Celebrity Face Matcher

## Overview
This application is developed by the **MongoDB PHP Team** as a demonstration tool for conferences and technical talks. It showcases the full capabilities of MongoDB's vector search features in a fun, interactive way. Built with Symfony and MongoDB Doctrine ODM, this face-matching service allows attendees to find which MongoDB team member or open-source contributor they most resemble.

## Demo Purpose
This project serves as a practical demonstration of:
- MongoDB vector search capabilities in real-world applications
- Symfony integration with MongoDB using Doctrine ODM
- Practical implementation of AI-generated embeddings
- Interactive web application development with Symfony UX

## How It Works

### Backend Flow
- Upload pictures of MongoDB team members and open-source contributors
- Application uses AI to generate descriptions of each person
- Generate vector embeddings from these descriptions
- Store name, resized image, description, and embeddings in MongoDB

### Matcher Flow
- Take a picture using the webcam at conference booths
- AI generates a description and embeddings of the captured person
- MongoDB vector search finds similar faces based on embedding similarity
- Display the closest matches with confidence scores
- Show a special message for high-confidence matches

### MongoDB Features Showcase
- **Vector Search**: Demonstrate MongoDB's vector search capabilities for similarity matching
- **GridFS Storage**: Store and retrieve images as binary data (limited to ~12 MB)
- **Partial TTL Index**: Automatically manage temporary images taken during conferences
- **Doctrine ODM Integration**: Show seamless integration with Symfony applications

## UI Design
- Optimized for conference kiosks (33%/50% width screens)
- Designed to work without scrolling for a better user experience
- Clean, intuitive interface for quick interactions at busy conference booths

## Installation

1. Clone the repository:
   ```bash
   git clone https://github.com/GromNaN/symfony-celebrity-lookalike.git
   cd symfony-celebrity-lookalike
   ```

2. Install dependencies:
   ```bash
   composer install
   ```

3. Configure the environment:
   ```bash
   cp .env.example .env
   ```
   Update the `.env` file with your MongoDB Atlas credentials.

4. Start the server:
   ```bash
   symfony server:start
   ```

5. Access the application at `http://127.0.0.1:8000`.

## Conference Usage

### Adding Team Members
1. Access the admin interface
2. Upload a clear picture of MongoDB team members or contributors
3. The system will automatically generate descriptions and embeddings
4. Add the name and other relevant details

### Conference Booth Operation
1. Set up the application on a kiosk at your conference booth
2. Invite attendees to capture their picture using the webcam
3. The application uses AI to analyze the face
4. MongoDB's vector search quickly finds and displays the closest matches
5. Special messages appear for high-confidence matches
6. Engage attendees in discussions about MongoDB's vector search capabilities

## Development
This project is developed by the MongoDB PHP Team, based on [mongodb-developer/celeb-matcher-farm](https://github.com/mongodb-developer/celeb-matcher-farm) and will eventually be migrated to [mongodb-labs](https://github.com/GromNaN/symfony-celebrity-lookalike).

## Technologies
- MongoDB Atlas with Vector Search
- Symfony 7.2.5
- MongoDB Doctrine ODM
- Symfony UX Components
- AI-powered description generation
- Vector embeddings for similarity matching

## Contributing
This is a showcase project by the MongoDB PHP Team. If you're interested in contributing, please reach out to the team or create a pull request against the repository.
