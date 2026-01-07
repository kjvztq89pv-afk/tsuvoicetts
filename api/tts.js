// api/tts.js - Vercel Serverless Function (Node.js)

export default async function handler(req, res) {
  // CORS Headers
  res.setHeader('Access-Control-Allow-Origin', '*');
  res.setHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
  res.setHeader('Access-Control-Allow-Headers', 'Content-Type, X-Api-Key');

  // Handle preflight
  if (req.method === 'OPTIONS') {
    return res.status(200).end();
  }

  // Configuration
  const GOOGLE_TTS_API_KEY = 'AIzaSyAzmnMAqjJnhRTv4XOsjsYkGH6kXb9YirE';
  const ALLOWED_SECRET = 'tsuvoice-4530-LeRt';

  // Only POST allowed
  if (req.method !== 'POST') {
    return res.status(405).json({ success: false, message: 'Method not allowed' });
  }

  // Verify API Key
  const apiKey = req.headers['x-api-key'] || '';
  if (apiKey !== ALLOWED_SECRET) {
    return res.status(403).json({ success: false, message: 'Invalid API Key' });
  }

  try {
    const { text, voice = 'th-TH-Neural2-C', speaking_rate = 1.0, pitch = 0.0 } = req.body;

    if (!text) {
      return res.status(400).json({ success: false, message: 'Text is required' });
    }

    if (text.length > 5000) {
      return res.status(400).json({ success: false, message: 'Text too long (max 5000 bytes)' });
    }

    // Call Google Cloud TTS API
    const googleUrl = `https://texttospeech.googleapis.com/v1/text:synthesize?key=${GOOGLE_TTS_API_KEY}`;

    const ttsRequest = {
      input: { text: text },
      voice: {
        languageCode: 'th-TH',
        name: voice
      },
      audioConfig: {
        audioEncoding: 'MP3',
        speakingRate: parseFloat(speaking_rate),
        pitch: parseFloat(pitch)
      }
    };

    const googleResponse = await fetch(googleUrl, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(ttsRequest),
    });

    const result = await googleResponse.json();

    if (!googleResponse.ok) {
      return res.status(googleResponse.status).json({
        success: false,
        message: result.error?.message || 'Google TTS API Error'
      });
    }

    if (!result.audioContent) {
      return res.status(500).json({ success: false, message: 'No audio content' });
    }

    return res.status(200).json({
      success: true,
      audioContent: result.audioContent,
      voice: voice
    });

  } catch (error) {
    console.error('TTS Error:', error);
    return res.status(500).json({
      success: false,
      message: error.message || 'Internal server error'
    });
  }
}