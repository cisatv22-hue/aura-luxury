import http from 'node:http';
import { createGzip } from 'node:zlib';
import { pipeline } from 'node:stream';

const targetPort = Number(process.env.GZIP_PROXY_TARGET_PORT ?? 8000);
const proxyPort = Number(process.env.GZIP_PROXY_PORT ?? 8001);

http.createServer((request, response) => {
    const upstream = http.request(
        {
            host: '127.0.0.1',
            port: targetPort,
            path: request.url,
            method: request.method,
            headers: {
                ...request.headers,
                'accept-encoding': 'identity',
            },
        },
        (upstreamResponse) => {
            const status = upstreamResponse.statusCode ?? 502;
            const contentType = upstreamResponse.headers['content-type'] ?? '';
            const compressible =
                request.method !== 'HEAD' &&
                status !== 204 &&
                status !== 304 &&
                /^(text\/|application\/(javascript|json|manifest\+json|wasm|xml))/.test(
                    contentType,
                );
            const headers = { ...upstreamResponse.headers };
            if (compressible) {
                delete headers['content-length'];
                delete headers.etag;
                headers['content-encoding'] = 'gzip';
                headers.vary = headers.vary
                    ? `${headers.vary}, Accept-Encoding`
                    : 'Accept-Encoding';
            }

            response.writeHead(status, headers);
            pipeline(
                upstreamResponse,
                ...(compressible ? [createGzip({ level: 6 })] : []),
                response,
                (error) => {
                    if (error) response.destroy(error);
                },
            );
        },
    );

    upstream.on('error', () => {
        if (!response.headersSent) response.writeHead(502);
        response.end('Upstream storefront is unavailable');
    });
    request.pipe(upstream);
}).listen(proxyPort, '127.0.0.1');
