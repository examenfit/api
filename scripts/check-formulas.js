#!/usr/bin/env node
// Reads a JSON array of TeX formulas on stdin, prints a JSON array of {index, message} for the ones katex cannot convert.
// Same renderer and output format as the katex CLI that the exports use (node_modules/katex/cli.js).
const katex = require('katex');

let input = '';
process.stdin.setEncoding('utf8');
process.stdin.on('data', (chunk) => (input += chunk));
process.stdin.on('end', () => {
    const formulas = JSON.parse(input);
    const failures = [];

    formulas.forEach((formula, index) => {
        try {
            const html = katex.renderToString(String(formula), { output: 'htmlAndMathml', strict: 'ignore' });

            if (!/<math.+<\/math>/.test(html)) {
                throw new Error('no MathML in the katex output');
            }
        } catch (error) {
            failures.push({ index, message: String(error.message).replace(/\s+/g, ' ') });
        }
    });

    process.stdout.write(JSON.stringify(failures));
});
