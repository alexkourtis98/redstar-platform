#!/usr/bin/env node

/**
 * DTCG Token → CSS Variables Compiler (Simplified)
 * Strategy: Build a flat map of ALL possible reference paths, then resolve
 */

import { readFileSync, writeFileSync, readdirSync, mkdirSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
const TOKENS_SOURCE = join(__dirname, '../../redstar/tokens');
const TOKENS_OUTPUT = join(__dirname, '../resources/css/tokens');

try {
  mkdirSync(TOKENS_OUTPUT, { recursive: true });
} catch (e) {}

/**
 * Build a flat lookup map of all token paths
 * Example: "primitive.blue.600" => "#2563EB"
 */
function buildTokenMap(tokens, prefix = '', map = {}) {
  for (const [key, value] of Object.entries(tokens)) {
    if (key.startsWith('$')) continue;

    const path = prefix ? `${prefix}.${key}` : key;

    if (value && typeof value === 'object') {
      if (value.$value !== undefined) {
        // This is a token
        map[path] = value.$value;
      } else if (!Array.isArray(value)) {
        // Recurse
        buildTokenMap(value, path, map);
      }
    }
  }

  return map;
}

/**
 * Resolve reference with multiple fallback strategies
 */
function resolveValue(value, tokenMap, visited = new Set()) {
  if (typeof value !== 'string') return value;

  const refMatch = value.match(/^\{([^}]+)\}$/);
  if (!refMatch) return value;

  const ref = refMatch[1];
  if (visited.has(ref)) {
    throw new Error(`Circular reference: ${ref}`);
  }

  visited.add(ref);

  // Try direct lookup
  if (tokenMap[ref]) {
    return resolveValue(tokenMap[ref], tokenMap, visited);
  }

  // Try alternate lookups for file-based references
  // Example: {dataviz.diverging.positive} might be stored as "diverging.positive"
  const parts = ref.split('.');
  if (parts.length > 1) {
    // Try without first part (filename)
    const withoutPrefix = parts.slice(1).join('.');
    if (tokenMap[withoutPrefix]) {
      return resolveValue(tokenMap[withoutPrefix], tokenMap, visited);
    }
  }

  throw new Error(`Reference not found: ${ref}`);
}

/**
 * Generate CSS variables
 */
function buildCSS() {
  console.log('Building design tokens...\n');

  const files = readdirSync(TOKENS_SOURCE).filter(f => f.endsWith('.json'));
  const allTokens = {};
  let darkTokens = null;

  // Deep merge helper
  function deepMerge(target, source) {
    for (const [key, value] of Object.entries(source)) {
      if (key.startsWith('$')) {
        target[key] = value;
        continue;
      }

      if (value && typeof value === 'object' && !Array.isArray(value)) {
        if (!target[key]) target[key] = {};
        deepMerge(target[key], value);
      } else {
        target[key] = value;
      }
    }
  }

  // Load all files with deep merge
  for (const file of files) {
    const content = readFileSync(join(TOKENS_SOURCE, file), 'utf-8');
    const tokens = JSON.parse(content);

    // Special handling for colors.json dark mode
    if (file === 'colors.json' && tokens.dark) {
      darkTokens = tokens.dark;
      delete tokens.dark;
    }

    // Deep merge into allTokens
    deepMerge(allTokens, tokens);
  }

  // Build flat lookup map
  const tokenMap = buildTokenMap(allTokens);
  console.log(`Loaded ${Object.keys(tokenMap).length} token paths`);

  // Debug: Show sample keys
  const sampleKeys = Object.keys(tokenMap).slice(0, 15);
  console.log(`  Sample keys:`, sampleKeys.join(', '));

  const semanticKeys = Object.keys(tokenMap).filter(k => k.startsWith('semantic'));
  console.log(`  Found ${semanticKeys.length} semantic.* keys (first 10):`, semanticKeys.slice(0, 10).join(', '));
  console.log();

  // Resolve all values and generate CSS
  const cssVars = {};
  for (const [path, value] of Object.entries(tokenMap)) {
    try {
      const resolved = resolveValue(value, tokenMap);
      const cssName = `--${path.replace(/\./g, '-')}`;
      cssVars[cssName] = resolved;
    } catch (e) {
      console.warn(`⚠ Skipped ${path}: ${e.message}`);
    }
  }

  // Generate CSS
  let css = `/**
 * Design Tokens - Auto-generated
 * DO NOT EDIT - Run npm run build:tokens
 */

:root {
`;

  for (const [name, value] of Object.entries(cssVars)) {
    css += `  ${name}: ${value};\n`;
  }

  css += '}\n';

  // Dark mode
  if (darkTokens) {
    const darkMap = buildTokenMap(darkTokens);
    const darkVars = {};

    for (const [path, value] of Object.entries(darkMap)) {
      try {
        const resolved = resolveValue(value, Object.assign({}, tokenMap, darkMap));
        const cssName = `--${path.replace(/\./g, '-')}`;
        darkVars[cssName] = resolved;
      } catch (e) {}
    }

    if (Object.keys(darkVars).length > 0) {
      css += `\n[data-theme="dark"] {\n`;
      for (const [name, value] of Object.entries(darkVars)) {
        css += `  ${name}: ${value};\n`;
      }
      css += '}\n';
    }
  }

  const outputPath = join(TOKENS_OUTPUT, 'redstar.css');
  writeFileSync(outputPath, css, 'utf-8');

  console.log(`✓ Generated ${Object.keys(cssVars).length} CSS variables`);
  console.log(`✓ Output: ${outputPath}\n`);
}

try {
  buildCSS();
} catch (error) {
  console.error('❌ Build failed:', error.message);
  process.exit(1);
}
