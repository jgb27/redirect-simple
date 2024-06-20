<?php

/*
Plugin Name: Redirect Simple
Plugin URI: 
Description: Plugin simples para WordPress que facilita a criação e gerenciamento de redirecionamentos de URLs de forma intuitiva e segura.
Author: João Gustavo S. Bispo
Version: 1.0
License: MIT 
*/

/*
LICENÇA MIT

Copyright (c) 2024 João Gustavo Soares Bispo

A permissão é concedida, gratuitamente, a qualquer pessoa que obtenha uma cópia deste software e dos arquivos de documentação associados (o "Software"), para lidar com o Software sem restrições, incluindo, sem limitação, os direitos de usar, copiar, modificar, mesclar, publicar, distribuir, sublicenciar e/ou vender cópias do Software, e permitir às pessoas a quem o Software é fornecido que o façam, sujeito às seguintes condições:

O aviso de copyright acima e este aviso de permissão deverão ser incluídos em todas as cópias ou partes substanciais do Software.

Atribuição — Você deve dar o crédito apropriado, prover um link para o repositório original e indicar se mudanças foram feitas. Você pode fazer isso de qualquer forma razoável, mas não de maneira que sugira que o licenciador endossa você ou seu uso.

O SOFTWARE É FORNECIDO "COMO ESTÁ", SEM GARANTIA DE QUALQUER TIPO, EXPRESSA OU IMPLÍCITA, INCLUINDO, MAS NÃO SE LIMITANDO ÀS GARANTIAS DE COMERCIALIZAÇÃO, ADEQUAÇÃO A UM PROPÓSITO PARTICULAR E NÃO VIOLAÇÃO. EM NENHUMA HIPÓTESE OS AUTORES OU DETENTORES DOS DIREITOS AUTORAIS SERÃO RESPONSÁVEIS POR QUALQUER RECLAMAÇÃO, DANOS OU OUTRA RESPONSABILIDADE, SEJA EM UMA AÇÃO DE CONTRATO, ATO ILÍCITO OU DE OUTRA FORMA, DECORRENTE DE, FORA DE OU EM CONEXÃO COM O SOFTWARE OU O USO OU OUTRAS NEGOCIAÇÕES NO SOFTWARE.
*/

defined('ABSPATH') or die('No script kiddies please!');

// Adiciona a página de configurações ao menu de administração
function rs_add_admin_menu()
{
    add_management_page(
        'Redirect',
        'Redirecionamento de links',
        'manage_options',
        'RS',
        'rs_setting_page',
        0
    );
}

add_action('admin_menu', 'rs_add_admin_menu');

// Enfileira a folha de estilo
function rs_enqueue_admin_style()
{
    wp_enqueue_style('rs_admin_styles', plugin_dir_url(__FILE__) . 'styles.css');
}
add_action('admin_enqueue_scripts', 'rs_enqueue_admin_style');

// Exibe a página de configurações
function rs_setting_page()
{
?>
    <div class="wrap">
        <h1>Redirect Simple</h1>
        <?php if (isset($_GET['settings-updated']) && $_GET['settings-updated']) : ?>
            <div id="message" class="updated notice is-dismissible">
                <p>Configurações salvas com sucesso.</p>
                <button type="button" class="notice-dismiss" id="btn-close-feedback">
                    <span class="screen-reader-text">Dispensar este aviso.</span>
                </button>
            </div>
        <?php endif; ?>
        <form method="post" action="options.php">
            <?php
            settings_fields('rs_settings_group');
            do_settings_sections('rs_settings');
            submit_button();
            ?>
        </form>
    </div>
<?php
}

// Inicializa as configurações
function rs_settings_init()
{
    register_setting('rs_settings_group', 'rs_redirects', 'rs_sanitize_redirects');

    add_settings_section(
        'rs_settings_section',
        'Configurações de Redirecionamento',
        'rs_settings_section_callback',
        'rs_settings'
    );

    add_settings_field(
        'rs_redirects',
        'Redirecionamentos',
        'rs_redirects_render',
        'rs_settings',
        'rs_settings_section'
    );
}
add_action('admin_init', 'rs_settings_init');

// Callback para a seção de configurações
function rs_settings_section_callback()
{
    echo 'Insira as URLs para redirecionamento. Use o botão "Novo Redirecionamento" para adicionar múltiplos redirecionamentos.';
}

// Renderiza o campo de redirecionamentos
function rs_redirects_render()
{
    $redirects = get_option('rs_redirects', []);
?>
    <div id="rs-redirects">
        <?php if (!empty($redirects)) : ?>
            <?php foreach ($redirects as $index => $redirect) : ?>
                <div class="rs-redirect">
                    <input type="text" name="rs_redirects[<?php echo $index; ?>][from]" value="<?php echo esc_attr($redirect['from']); ?>" placeholder="Redirecionar De">
                    <input type="text" name="rs_redirects[<?php echo $index; ?>][to]" value="<?php echo esc_attr($redirect['to']); ?>" placeholder="Redirecionar Para">
                    <button type="button" class="button rs-remove-redirect">Remover</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <button type="button" class="button" id="rs-add-redirect">Novo Redirecionamento</button>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            var redirectsDiv = document.getElementById('rs-redirects');
            var btnCloseFeedback = document.getElementById('btn-close-feedback');
            var addRedirectBtn = document.getElementById('rs-add-redirect');

            // Adiciona novo redirecionamento
            addRedirectBtn.addEventListener('click', function() {
                var index = redirectsDiv.children.length;
                var newRedirect = document.createElement('div');
                newRedirect.className = 'rs-redirect';
                newRedirect.innerHTML = '<input type="text" name="rs_redirects[' + index + '][from]" placeholder="Redirecionar De"> ' +
                    '<input type="text" name="rs_redirects[' + index + '][to]" placeholder="Redirecionar Para"> ' +
                    '<button type="button" class="button rs-remove-redirect">Remover</button>';
                redirectsDiv.appendChild(newRedirect);
            });

            // Remove redirecionamento
            redirectsDiv.addEventListener('click', function(e) {
                if (e.target && e.target.classList.contains('rs-remove-redirect')) {
                    e.target.parentNode.remove();
                }
            });

            // Fecha feedback de sucesso
            btnCloseFeedback.addEventListener('click', function(e) {
                var msg = document.getElementById('message');
                msg.style.display = 'none';
            });
        });
    </script>
<?php
}

// Sanitiza os redirecionamentos
function rs_sanitize_redirects($redirects)
{
    $sanitized_redirects = [];
    foreach ($redirects as $redirect) {
        if (!empty($redirect['from']) && !empty($redirect['to']) && filter_var($redirect['to'], FILTER_VALIDATE_URL)) {
            $sanitized_redirects[] = [
                'from' => sanitize_text_field($redirect['from']),
                'to' => sanitize_text_field($redirect['to']),
            ];
        }
    }
    return $sanitized_redirects;
}

// Realiza o redirecionamento
function rs_redirect()
{
    if (!is_admin() && !defined('DOING_AJAX')) {
        $redirects = get_option('rs_redirects', []);
        $request_uri = $_SERVER['REQUEST_URI'];

        foreach ($redirects as $redirect) {
            if (strpos($request_uri, $redirect['from']) !== false) {
                wp_redirect($redirect['to'], 301);
                exit;
            }
        }
    }
}
add_action('template_redirect', 'rs_redirect');
?>