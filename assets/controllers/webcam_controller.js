import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = [
        'startScreen', 
        'cameraView', 
        'previewView', 
        'resultsView', 
        'loadingView',
        'video', 
        'canvas', 
        'resultsContainer'
    ];
    
    connect() {
        // Set canvas dimensions based on standard 4:3 aspect ratio
        this.canvasTarget.width = 640;
        this.canvasTarget.height = 480;
    }
    
    async startCamera() {
        try {
            const stream = await navigator.mediaDevices.getUserMedia({ 
                video: { 
                    width: { ideal: 640 },
                    height: { ideal: 480 },
                    facingMode: "user"
                }, 
                audio: false 
            });
            
            this.videoTarget.srcObject = stream;
            this.videoTarget.play();
            
            // Show camera view, hide other views
            this.startScreenTarget.classList.add('hidden');
            this.cameraViewTarget.classList.remove('hidden');
        } catch (error) {
            console.error('Error accessing camera:', error);
            alert('Could not access your camera. Please allow camera access and try again.');
        }
    }
    
    takePicture() {
        // Draw the video frame to the canvas
        const context = this.canvasTarget.getContext('2d');
        context.drawImage(
            this.videoTarget, 
            0, 0, 
            this.canvasTarget.width, 
            this.canvasTarget.height
        );
        
        // Stop the camera
        this._stopCamera();
        
        // Show preview view, hide other views
        this.cameraViewTarget.classList.add('hidden');
        this.previewViewTarget.classList.remove('hidden');
    }
    
    retakePicture() {
        // Clear the canvas
        const context = this.canvasTarget.getContext('2d');
        context.clearRect(0, 0, this.canvasTarget.width, this.canvasTarget.height);
        
        // Restart camera
        this.startCamera();
        
        // Show camera view, hide other views
        this.previewViewTarget.classList.add('hidden');
    }
    
    async validatePicture() {
        // Show loading view
        this.previewViewTarget.classList.add('hidden');
        this.loadingViewTarget.classList.remove('hidden');
        
        try {
            // Convert canvas to blob
            const blob = await new Promise(resolve => {
                this.canvasTarget.toBlob(blob => resolve(blob), 'image/jpeg', 0.9);
            });
            
            // Create FormData and append the image
            const formData = new FormData();
            formData.append('picture', blob, 'webcam-picture.jpg');
            
            // Send to server
            const response = await fetch('/process', {
                method: 'POST',
                body: formData
            });
            
            if (!response.ok) {
                throw new Error('Server responded with an error');
            }
            
            const data = await response.json();
            
            // Process and display the matches
            this._displayResults(data.matches);
            
            // Show results view, hide loading view
            this.loadingViewTarget.classList.add('hidden');
            this.resultsViewTarget.classList.remove('hidden');
        } catch (error) {
            console.error('Error processing picture:', error);
            alert('An error occurred while processing your picture. Please try again.');
            
            // Go back to start screen
            this.loadingViewTarget.classList.add('hidden');
            this.startScreenTarget.classList.remove('hidden');
        }
    }
    
    restart() {
        // Clear results
        this.resultsContainerTarget.innerHTML = '';
        
        // Show start screen, hide other views
        this.resultsViewTarget.classList.add('hidden');
        this.startScreenTarget.classList.remove('hidden');
    }
    
    _stopCamera() {
        // Stop all video tracks
        if (this.videoTarget.srcObject) {
            const tracks = this.videoTarget.srcObject.getTracks();
            tracks.forEach(track => track.stop());
            this.videoTarget.srcObject = null;
        }
    }
    
    _displayResults(matches) {
        // Clear previous results
        this.resultsContainerTarget.innerHTML = '';
        
        if (!matches || matches.length === 0) {
            this.resultsContainerTarget.innerHTML = '<p class="text-white col-span-2 text-center">No matches found</p>';
            return;
        }
        
        // Create a card for each match
        matches.forEach(match => {
            const matchCard = document.createElement('div');
            matchCard.className = 'bg-gray-700 rounded-lg p-4 flex flex-col items-center';
            
            // Create image if available
            if (match.image) {
                const img = document.createElement('img');
                img.src = 'data:image/jpeg;base64,' + match.image;
                img.alt = match.name || 'Match';
                img.className = 'w-full h-auto rounded-lg mb-2';
                matchCard.appendChild(img);
            }
            
            // Create name
            const name = document.createElement('h3');
            name.className = 'text-lg font-bold text-white';
            name.textContent = match.name || 'Unknown';
            matchCard.appendChild(name);
            
            // Create score if available
            if (match.score) {
                const score = document.createElement('p');
                score.className = 'text-sm text-gray-300';
                score.textContent = `Match: ${Math.round(match.score * 100)}%`;
                matchCard.appendChild(score);
            }
            
            this.resultsContainerTarget.appendChild(matchCard);
        });
    }
    
    disconnect() {
        this._stopCamera();
    }
}
