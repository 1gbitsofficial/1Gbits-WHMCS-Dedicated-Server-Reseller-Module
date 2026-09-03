const { chromium } = require('playwright');
const path = require('path');
(async () => {
  const b = await chromium.launch();
  const src = f => 'file://' + path.resolve(__dirname, f);
  // icon: 512 and 200 from vector
  for (const [size, out] of [[512, '../icon-512.png'], [200, '../../logo.png']]) {
    const p = await b.newPage({ viewport: { width: 512, height: 512 }, deviceScaleFactor: size / 512 });
    await p.goto(src('icon.html')); await p.waitForTimeout(200);
    await p.locator('#i').screenshot({ path: path.resolve(__dirname, out), omitBackground: true });
    await p.close();
  }
  // screenshots
  for (const [f, out] of [['twentyone.html', '../screenshots/01-client-area-twenty-one.png'], ['six.html', '../screenshots/02-client-area-six.png']]) {
    const p = await b.newPage({ viewport: { width: 1440, height: 960 }, deviceScaleFactor: 1 });
    await p.goto(src(f)); await p.waitForTimeout(200);
    await p.screenshot({ path: path.resolve(__dirname, out), fullPage: false });
    await p.close();
  }
  await b.close();
})();
