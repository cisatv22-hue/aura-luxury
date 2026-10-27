import { expect, test } from '@playwright/test';

test('the storefront presents distinct content and no unconfirmed shipping promises', async ({
    page,
}) => {
    await page.goto('/');

    await expect(
        page.getByText('ATENCIÓN PERSONALIZADA POR WHATSAPP · PEDIDOS SUJETOS A CONFIRMACIÓN'),
    ).toBeVisible();
    await expect(
        page.getByRole('heading', { name: 'Nuevas piezas' }),
    ).toBeVisible();
    await expect(
        page.getByRole('heading', { name: 'Favoritos de AURA' }),
    ).toHaveCount(0);
    await expect(
        page.getByRole('heading', { name: 'Explora AURA' }),
    ).toBeVisible();
    await expect(page.getByRole('link', { name: 'Envíos' })).toHaveCount(0);

    const imageErrors = await page.locator('img').evaluateAll(async (images) => {
        await Promise.all(
            images.map(async (image) => {
                image.loading = 'eager';
                try {
                    await image.decode();
                } catch {
                    // Return the image below so the assertion reports its source.
                }
            }),
        );

        return images
            .filter((image) => !image.complete || image.naturalWidth === 0)
            .map((image) => image.currentSrc || image.src);
    });

    expect(imageErrors).toEqual([]);

    const productImages = await page.locator('article').evaluateAll((cards) =>
        cards
            .map((card) => card.querySelector('img')?.currentSrc)
            .filter((source): source is string => Boolean(source)),
    );
    expect(new Set(productImages).size).toBeGreaterThanOrEqual(
        Math.min(3, productImages.length),
    );
});
