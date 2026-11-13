// @ts-check
import { test, expect } from '@playwright/test';
import { pages } from './page.js';

const baseURL = process.env.SITE_URL;

pages.forEach(url => {

  test(`check console error on ${url}`, async ({ page }) => {
    let errorMsg = [];
    
    // Listen to console messages and collect errors, logs, and warnings
    page.on('console', msg => {
      if (msg.type() === 'error' || msg.type() === 'log' || msg.type() === 'warning') {
        const text = msg.text();
        // Messages du mode développement (BrowserSync) : ignorés
        if (/browsersync|browser-sync/i.test(text)) return;
        errorMsg.push(text);
      }
    });

    await page.goto(url);

    await expect(errorMsg.length).toBe(0);
  });


  test.skip(`check meta description on ${url}`, async ({ page }) => {
  
    const metaDescription = page.locator('meta[name="description"]');

    await page.goto(url);

    await expect(metaDescription).toHaveAttribute('content', /.+/);

  });

  test(`check broken links on ${url}`, async ({ page }) => {
    await page.goto(url); // ← d'abord on charge la page

    const links = await page.$$eval('a', anchors => 
      anchors.map(a => a.href)
    );
    const filteredLinks = links.filter(link => link.startsWith(baseURL));

    for (const link of filteredLinks) {
      try {
        const response = await page.request.get(link)
        expect
          .soft(response.ok(), `❌ Lien cassé : ${link} (trouvé sur ${url})`)
          .toBeTruthy()
      } catch {
        expect.soft(null, `❌ Lien cassé : ${link} (trouvé sur ${url})`).toBeTruthy()
      }
    }
  })

  test(`check time wait on ${url}`, async ({ page }) => {
    
    const startTime = Date.now();
    await page.goto(url); // ← d'abord on charge la page
    const endTime = Date.now();

    const loadTime = endTime - startTime;

    expect.soft(loadTime, `⏱️ Temps de chargement élevé : ${loadTime}ms (sur ${url})`).toBeLessThan(10000);
  
  });

  test(`check image alt on ${url}`, async ({ page }) => {
    await page.goto(url); // ← d'abord on charge la page

    const images = await page.$$eval('img', imgs => 
      imgs.map(img => ({ src: img.src, alt: img.alt }))
    );

    for (const { src, alt } of images) {
      expect.soft(alt, `🖼️ Image sans alt : ${src} (trouvé sur ${url})`).not.toBe('');
    }

  });

  // test link with noreferrer
  test(`check external links have noreferrer on ${url}`, async ({ page }) => {
    await page.goto(url); 

    const externalLinks = await page.$$eval('a[target="_blank"]', anchors => 
      anchors.map(a => ({ href: a.getAttribute('href') || '', rel: a.getAttribute('rel') || '' }))
    );

    for (const { href, rel } of externalLinks) {
      if (!href.startsWith(baseURL)) {
        expect.soft(rel, `Lien externe sans noreferrer : ${href} (trouvé sur ${url})`).toContain('noreferrer');
      }
    }

  });

});


