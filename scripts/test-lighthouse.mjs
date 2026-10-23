import lighthouse from 'lighthouse';
import { launch } from 'chrome-launcher';
import { chromium } from '@playwright/test';

const baseUrl = process.env.LIGHTHOUSE_BASE_URL ?? 'http://127.0.0.1:8000';
const paths = [
    '/',
    '/',
    '/',
    '/',
    '/',
    '/tienda',
    '/producto/sudadera-prueba-e2e',
];
const chromeOptions = {
    chromePath: chromium.executablePath(),
    chromeFlags: ['--headless', '--no-sandbox', '--disable-gpu'],
};
let chrome;
let failedPaths = [];
const homeLcpMeasurements = [];

try {
    for (const path of paths) {
        if (path === '/' || !chrome) {
            chrome?.kill();
            chrome = await launch(chromeOptions);
        }
        let result;
        for (let attempt = 1; attempt <= 2; attempt++) {
            result = await lighthouse(
                new URL(path, baseUrl).toString(),
                {
                    port: chrome.port,
                    logLevel: 'error',
                    onlyCategories: ['performance'],
                    formFactor: 'mobile',
                    screenEmulation: {
                        mobile: true,
                        width: 390,
                        height: 844,
                        deviceScaleFactor: 3,
                        disabled: false,
                    },
                },
                { extends: 'lighthouse:default' },
            );

            if (
                !result?.lhr?.runtimeError &&
                result?.lhr?.categories?.performance
            ) {
                break;
            }

            const error = result?.lhr?.runtimeError;
            if (attempt < 2) {
                console.warn(
                    `  Lighthouse could not audit ${path}${error ? ` (${error.code}: ${error.message})` : ''}; retrying once.`,
                );
            }
        }

        if (
            result?.lhr?.runtimeError ||
            !result?.lhr?.categories?.performance
        ) {
            const error = result?.lhr?.runtimeError;
            failedPaths.push(
                error
                    ? `${path} runtime error ${error.code}: ${error.message}`
                    : `${path} returned no Lighthouse result`,
            );
            continue;
        }

        const score = result?.lhr.categories.performance.score ?? 0;
        const percentage = Math.round(score * 100);
        console.log(`${path}: Lighthouse mobile performance ${percentage}`);
        const lcpMs =
            result?.lhr.audits['largest-contentful-paint']?.numericValue;
        if (path === '/') {
            if (typeof lcpMs !== 'number') {
                failedPaths.push('home LCP measurement is unavailable');
            } else {
                homeLcpMeasurements.push(lcpMs);
            }
        }
        for (const metric of [
            'first-contentful-paint',
            'largest-contentful-paint',
            'speed-index',
            'total-blocking-time',
            'cumulative-layout-shift',
            'server-response-time',
        ]) {
            const audit = result?.lhr.audits[metric];
            const value =
                metric === 'largest-contentful-paint' &&
                typeof audit?.numericValue === 'number'
                    ? `${Math.round(audit.numericValue)} ms (${audit.displayValue})`
                    : (audit?.displayValue ?? audit?.numericValue);
            console.log(`  ${audit?.title}: ${value}`);
        }
        if (score < 0.85) {
            failedPaths.push(`${path} scored ${percentage}`);
        }

        const audits = Object.values(result?.lhr.audits ?? {})
            .filter(
                (audit) =>
                    audit.score !== null &&
                    audit.score < 0.9 &&
                    (audit.details?.overallSavingsMs ||
                        audit.details?.overallSavingsBytes),
            )
            .sort(
                (first, second) =>
                    (second.details?.overallSavingsMs ?? 0) -
                    (first.details?.overallSavingsMs ?? 0),
            )
            .slice(0, 8);
        for (const audit of audits) {
            console.log(
                `  ${audit.title}: ${audit.displayValue ?? audit.description}`,
            );
            for (const item of audit.details?.items?.slice(0, 4) ?? []) {
                console.log(
                    `    ${item.url ?? item.node?.snippet ?? item.label ?? ''} ${item.wastedMs ? `${Math.round(item.wastedMs)}ms` : ''} ${item.wastedBytes ? `${Math.round(item.wastedBytes / 1024)}KiB` : ''}`,
                );
            }
        }
        for (const id of [
            'render-blocking-resources',
            'font-display',
            'largest-contentful-paint-element',
            'lcp-breakdown-insight',
            'lcp-discovery-insight',
        ]) {
            const audit = result?.lhr.audits[id];
            if (
                audit &&
                (audit.score !== 1 ||
                    id === 'largest-contentful-paint-element' ||
                    id.startsWith('lcp-'))
            ) {
                console.log(
                    `  ${audit.title}: ${audit.displayValue ?? audit.description}`,
                );
                if (id.startsWith('lcp-')) {
                    for (const detail of audit.details?.items ?? []) {
                        if (detail.type === 'table') {
                            for (const item of detail.items ?? []) {
                                console.log(
                                    `    ${item.label}: ${Math.round(item.duration)}ms`,
                                );
                            }
                        } else if (detail.type === 'node') {
                            console.log(
                                `    LCP element: ${detail.nodeLabel} (${detail.selector})`,
                            );
                        } else if (detail.type === 'checklist') {
                            for (const item of Object.values(
                                detail.items ?? {},
                            )) {
                                console.log(
                                    `    ${item.label}: ${item.value ? 'yes' : 'no'}`,
                                );
                            }
                        }
                    }
                } else {
                    for (const item of audit.details?.items?.slice(0, 4) ??
                        []) {
                        console.log(
                            `    ${item.url ?? item.node?.snippet ?? ''} ${item.wastedMs ? `${Math.round(item.wastedMs)}ms` : ''}`,
                        );
                    }
                }
            }
        }
    }
} finally {
    chrome?.kill();
}

if (homeLcpMeasurements.length !== 5) {
    failedPaths.push(
        `home LCP has ${homeLcpMeasurements.length} valid measurements; expected 5`,
    );
} else {
    const sortedHomeLcp = [...homeLcpMeasurements].sort(
        (first, second) => first - second,
    );
    const medianHomeLcp = sortedHomeLcp[2];
    console.log(
        `Home LCP median (5 independent mobile runs): ${Math.round(medianHomeLcp)} ms`,
    );
    if (medianHomeLcp >= 2500) {
        failedPaths.push(
            `home LCP median ${Math.round(medianHomeLcp)} ms exceeds 2500 ms`,
        );
    }
}

if (failedPaths.length) {
    throw new Error(
        `Lighthouse mobile quality gates failed: ${failedPaths.join(', ')}`,
    );
}
