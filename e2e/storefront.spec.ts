import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';

const productPath = '/producto/sudadera-prueba-e2e';

test('filters the catalog and keeps the selected filters in the URL', async ({
    page,
}) => {
    await page.goto('/tienda');
    await expect(page.getByRole('heading', { name: 'Tienda' })).toBeVisible();

    await page.getByLabel('Talla').selectOption('M');
    await expect(page).toHaveURL(/size=M/);
    await expect(
        page.getByRole('link', { name: 'Sudadera de prueba E2E' }).first(),
    ).toBeVisible();

    await page.getByLabel('Ordenar').selectOption('price_asc');
    await expect(page).toHaveURL(/sort=price_asc/);
});

test('focuses the search field with the slash keyboard shortcut', async ({
    page,
}) => {
    await page.goto('/');
    await page.getByRole('button', { name: /Abrir bolsa/ }).click();
    await expect(page.getByRole('dialog', { name: /Tu bolsa/ })).toBeVisible();
    await page.getByRole('button', { name: 'Cerrar bolsa' }).click();
    await page.keyboard.press('/');

    await expect(
        page.getByRole('textbox', { name: 'Buscar productos' }),
    ).toBeFocused();
});

test('navigates search suggestions with the arrow keys', async ({ page }) => {
    await page.goto('/tienda');
    const search = page.getByRole('combobox', { name: 'Buscar' });
    await search.fill('Sudadera de prueba E2E');

    const suggestion = page.getByRole('option', {
        name: 'Sudadera de prueba E2E',
    });
    await expect(suggestion).toBeVisible();
    await search.press('ArrowDown');
    await expect(suggestion).toHaveAttribute('aria-selected', 'true');
    await search.press('Enter');

    await expect(page).toHaveURL(/producto\/sudadera-prueba-e2e/);
});

test('loads the next catalog page without replacing the first products', async ({
    page,
}) => {
    await page.goto('/tienda');
    await expect(page.getByText('24 productos mostrados')).toBeVisible();
    await page.getByRole('button', { name: 'Cargar más' }).click();

    await expect(page).toHaveURL(/page=2/);
    await expect(page.getByText('25 productos mostrados')).toBeVisible();
    await expect(
        page.getByRole('link', { name: 'Sudadera prueba paginación 24' }),
    ).toBeVisible();
});

test('completes the product to WhatsApp order flow', async ({ page }) => {
    await page.goto(productPath);
    await expect(
        page.getByRole('heading', { name: 'Sudadera de prueba E2E' }),
    ).toBeVisible();

    await page.getByRole('button', { name: 'M', exact: true }).click();
    await page.getByRole('button', { name: 'Agregar a la bolsa' }).click();
    await page.getByRole('button', { name: /Abrir bolsa/ }).click();
    await page.getByRole('link', { name: 'Continuar con mi pedido' }).click();

    await page.getByLabel('Nombre completo').fill('Cliente de prueba E2E');
    await page.getByLabel('Celular').fill('5512345678');
    await page.getByRole('textbox', { name: 'Código postal' }).fill('06000');
    await page.getByRole('button', { name: 'Cotizar envío' }).click();
    await expect(page.getByRole('status')).toContainText('Envío de prueba');
    await page
        .getByRole('textbox', { name: 'Ciudad' })
        .fill('Ciudad de México');
    await page
        .getByRole('button', { name: 'Enviar pedido por WhatsApp' })
        .click();

    await expect(page).toHaveURL(/\/pedido\/AURA-\d{4}-\d{4}/);
    await expect(page.getByText(/AURA-\d{4}-\d{4}/)).toBeVisible();
});

test('opens and navigates the selected color photo gallery', async ({
    page,
}) => {
    await page.goto(productPath);
    await expect(
        page.getByRole('heading', { name: 'Sudadera de prueba E2E' }),
    ).toBeVisible();

    await page.getByRole('button', { name: 'Ampliar foto 1 de 2' }).click();
    const gallery = page.getByRole('dialog', {
        name: 'Galería de imágenes del producto',
    });
    await expect(gallery).toBeVisible();
    await expect(gallery.getByText('1 / 2')).toBeVisible();
    await gallery.getByRole('button', { name: 'Foto siguiente' }).click();
    await expect(gallery.getByText('2 / 2')).toBeVisible();
    await gallery.getByRole('button', { name: 'Cerrar galería' }).click();
    await expect(gallery).not.toBeVisible();
});

test('shows low stock for the selected size independently from other sizes', async ({
    page,
}) => {
    await page.goto(productPath);
    await page.getByRole('button', { name: 'S', exact: true }).click();
    await expect(page.getByRole('status')).toContainText('Quedan 2');

    await page.getByRole('button', { name: 'M', exact: true }).click();
    await expect(page.getByText('Quedan 2')).not.toBeVisible();
});

test('recommends a size from configured height and weight ranges', async ({
    page,
}) => {
    await page.goto(productPath);
    await page.getByRole('button', { name: 'Guía de tallas' }).click();
    const guide = page.getByRole('dialog', { name: 'Guía E2E' });
    await guide.getByLabel('Estatura (cm)').fill('170');
    await guide.getByLabel('Peso (kg)').fill('70');
    await expect(guide).toContainText('Te recomendamos la talla M.');
    await expect(guide.locator('table')).not.toContainText('_height_min_cm');
});

test('restores a removed bag item with undo', async ({ page }) => {
    await page.goto(productPath);
    await page.getByRole('button', { name: 'M', exact: true }).click();
    await page.getByRole('button', { name: 'Agregar a la bolsa' }).click();
    await page.getByRole('button', { name: /Abrir bolsa/ }).click();
    await page
        .getByRole('button', {
            name: 'Quitar una unidad de Sudadera de prueba E2E',
        })
        .click();

    await expect(page.getByText('Tu bolsa está vacía')).toBeVisible();
    await page.getByRole('button', { name: 'Deshacer' }).click();
    await expect(
        page.getByRole('heading', { name: /Tu bolsa \(1\)/ }),
    ).toBeVisible();
    await expect(
        page.getByRole('link', { name: 'Sudadera de prueba E2E' }),
    ).toBeVisible();
});

test('renders the product before client-side JavaScript runs', async ({
    browser,
}) => {
    const context = await browser.newContext({ javaScriptEnabled: false });
    const page = await context.newPage();

    await page.goto(productPath);
    await expect(
        page.getByRole('heading', { name: 'Sudadera de prueba E2E' }),
    ).toBeVisible();

    await context.close();
});

for (const path of ['/', '/tienda', productPath]) {
    test(`has no WCAG 2.1 A/AA violations on ${path}`, async ({ page }) => {
        await page.goto(path);
        await expect(page.locator('h1').first()).toBeVisible();

        if (path === '/tienda') {
            const pagination = page.getByRole('navigation', {
                name: 'Paginación',
            });
            await expect(
                pagination.getByRole('link', { name: 'Página 1, actual' }),
            ).toBeVisible();
            await expect(
                pagination.getByRole('link', { name: 'Página siguiente' }),
            ).toBeVisible();
        }

        const results = await new AxeBuilder({ page })
            .withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa'])
            .analyze();
        const violations = results.violations.map((violation) => ({
            id: violation.id,
            impact: violation.impact,
            description: violation.description,
            elements: violation.nodes.map((node) => node.target.join(', ')),
        }));

        expect(violations).toEqual([]);
    });
}
