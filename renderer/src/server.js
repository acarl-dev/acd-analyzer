import express from 'express';
import { chromium } from 'playwright';

const app = express();
const port = process.env.PORT || 3001;

app.use(express.json({ limit: '1mb' }));

app.get('/health', (request, response) => {
  response.json({
    status: 'ok',
    service: 'acd-renderer',
  });
});

app.post('/render', async (request, response) => {
  const { url } = request.body;

  if (!url || typeof url !== 'string') {
    return response.status(422).json({
      error: 'The url field is required.',
    });
  }

  try {
    const parsedUrl = new URL(url);

    if (!['http:', 'https:'].includes(parsedUrl.protocol)) {
      return response.status(422).json({
        error: 'Only http and https URLs are supported.',
      });
    }
  } catch (error) {
    return response.status(422).json({
      error: 'The url field must be a valid URL.',
    });
  }

  let browser;

  try {
    browser = await chromium.launch({
      headless: true,
    });

    const page = await browser.newPage();

    const pageResponse = await page.goto(url, {
      waitUntil: 'networkidle',
      timeout: 15000,
    });

    const html = await page.content();

    return response.json({
      url,
      status: pageResponse?.status() ?? null,
      html,
    });
  } catch (error) {
    return response.status(500).json({
      error: 'Rendering failed.',
      detail: error.message,
    });
  } finally {
    if (browser) {
      await browser.close();
    }
  }
});

app.listen(port, () => {
  console.log(`ACD Renderer listening on port ${port}`);
});