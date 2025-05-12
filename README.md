# Symfony/Doctrine Celebrity Face Matcher

## Overview
Discover the power of AI-driven celebrity face matching with our Symfony application. Designed for conferences and events, this app combines cutting-edge technology with a user-friendly interface to deliver seamless face recognition and matching.

## Features

### Backend
- **Effortless Uploads**: Upload and store pictures as binary data (up to 12 MB).
- **AI-Powered Insights**: Automatically generate descriptions and embeddings for uploaded images.
- **Smart Storage**: Save names, resized images, descriptions, and embeddings in MongoDB.
- **Optimized for Events**: Partial TTL index ensures efficient management of conference photos.

### Matcher Flow
- **Instant Capture**: Take pictures directly using a webcam.
- **AI Matching**: Leverage AI to find the closest matches in the database.
- **Confidence Alerts**: Receive special messages for high-confidence matches.

### UI
- **Conference-Ready Design**: Optimized for 33%/50% width screens, ensuring a smooth experience without scrolling.

## Hosting
- **Database**: Hosted on MongoDB Atlas.
- **Application**: Deployable on platform.sh for reliable performance.

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

5. Access the app at `http://127.0.0.1:8000`.

## Usage

### Uploading Pictures
1. Navigate to the upload page.
2. Upload a picture to generate a description and embeddings.
3. Stored data is ready for matching.

### Matching Faces
1. Capture a picture using the webcam.
2. AI generates a description and finds matches.
3. View results and confidence messages.

## Contributing
We welcome contributions! Fork the repository and submit a pull request.

## License
Licensed under the MIT License.
