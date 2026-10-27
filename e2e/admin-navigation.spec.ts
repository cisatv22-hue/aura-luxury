import { expect, test } from '@playwright/test';

test('admin account can open the product manager from the account sidebar', async ({
    page,
}, testInfo) => {
    const email = process.env.PLAYWRIGHT_ADMIN_EMAIL;
    const password = process.env.PLAYWRIGHT_ADMIN_PASSWORD;

    test.skip(
        !email || !password,
        'Set temporary admin credentials to run this check.',
    );

    await page.goto('/login');
    await page.getByLabel('Email address').fill(email!);
    await page.getByLabel('Password', { exact: true }).fill(password!);
    await page.locator('[data-test="login-button"]').click();
    await page.waitForURL(/settings\/security|dashboard|cuenta|admin/);

    await page.goto('/settings/security');
    if (new URL(page.url()).pathname === '/user/confirm-password') {
        await page.getByLabel('Password', { exact: true }).fill(password!);
        await page.locator('[data-test="confirm-password-button"]').click();
    }
    await expect(page).toHaveURL(/\/settings\/security/);
    if (testInfo.project.name === 'mobile-chromium') {
        await page.goto('/admin');
    } else {
        const adminLink = page
            .getByRole('link', { name: 'Administrar tienda' })
            .first();
        await expect(adminLink).toBeVisible();
        await adminLink.click();
        await expect(page).toHaveURL(/\/admin\/products/);
    }

    await page.goto('/admin');
    await expect(page.getByText('¿Qué quieres hacer hoy?')).toBeVisible();
    await expect(page.getByText('Pedidos recientes')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Agregar producto' })).toBeVisible();
    const workspaceLayout = page
        .getByText('¿Qué quieres hacer hoy?')
        .locator('xpath=../../..');
    await expect
        .poll(() =>
            workspaceLayout.evaluate((element) =>
                getComputedStyle(element).display,
            ),
        )
        .toBe('grid');
    await page.goto('/admin/products');
    await expect(page).toHaveURL(/\/admin\/products/);
    await expect(page.getByRole('heading', { name: /productos/i })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Pedidos' })).toBeVisible();
    await expect(page.getByRole('link', { name: 'Clientes' })).toBeVisible();
    await page.getByPlaceholder('Buscar').first().fill('DEMO · Sudadera Essentials');
    await expect(
        page.getByRole('link', { name: 'DEMO · Sudadera Essentials' }).first(),
    ).toBeVisible();
});
