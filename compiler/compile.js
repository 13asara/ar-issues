#!/usr/bin/env node

import fs from 'node:fs';
import path from 'node:path';
import { loadImage } from 'canvas';
import { OfflineCompiler } from 'mind-ar/src/image-target/offline-compiler.js';

const args = process.argv.slice(2);
const jobIndex = args.indexOf('--job');

if (jobIndex === -1 || !args[jobIndex + 1]) {
  console.error('Usage: node compile.js --job /path/to/job.json');
  process.exit(2);
}

const job = JSON.parse(fs.readFileSync(args[jobIndex + 1], 'utf8'));

async function report(status, log) {
  const { url, token, issue } = job.callback;
  if (!url) return;
  try {
    const res = await fetch(url, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Mind-Token': token,
      },
      body: JSON.stringify({ issue, status, log: String(log).slice(0, 10000) }),
    });
    console.log(`callback ${url} -> ${res.status}`);
  } catch (err) {
    console.error('callback failed:', err.message);
  }
}

async function main() {
  if (!Array.isArray(job.images) || job.images.length === 0) {
    throw new Error('job.images is empty');
  }

  const images = [];
  for (const imgPath of job.images) {
    if (!fs.existsSync(imgPath)) {
      throw new Error(`image not found: ${imgPath}`);
    }
    images.push(await loadImage(imgPath));
    console.log(`loaded: ${path.basename(imgPath)}`);
  }

  const compiler = new OfflineCompiler();

  await compiler.compileImageTargets(images, (progress) => {
    console.log(`compiling: ${progress.toFixed(1)}%`);
  });

  const buffer = await compiler.exportData();

  fs.mkdirSync(path.dirname(job.output), { recursive: true });
  fs.writeFileSync(job.output, buffer);
  console.log(`written: ${job.output} (${buffer.length} bytes)`);

  await report('ready', `compiled ${images.length} target(s), ${(buffer.length / 1024).toFixed(0)} KB`);
}

main().catch(async (err) => {
  console.error(err);
  await report('failed', `${err}\n${err.stack ?? ''}`);
  process.exit(1);
});
