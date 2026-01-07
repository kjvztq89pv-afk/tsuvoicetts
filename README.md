# TTS Proxy Server

Proxy server for Google Cloud Text-to-Speech API.

## Deploy to Vercel

1. Fork this repository
2. Go to [Vercel](https://vercel.com)
3. Import the forked repository
4. Deploy

## Usage

```bash
POST /api/tts
Content-Type: application/json
X-Api-Key: your-secret-key

{
  "text": "สวัสดีครับ",
  "voice": "th-TH-Neural2-C",
  "speaking_rate": 1.0,
  "pitch": 0.0
}
```

## Response

```json
{
  "success": true,
  "audioContent": "base64-encoded-audio",
  "voice": "th-TH-Neural2-C"
}
```
