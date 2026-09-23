# Roadmap de Melhorias — `chrishenrique/livewire-modal-crud`

Documento de trabalho, atualizado em 2026-09-05, logo após o fechamento da 2.0.0.

A versão anterior deste arquivo era o diagnóstico que originou a 2.0.0. Todos os
bugs que ele listava foram corrigidos e estão registrados item a item no
[CHANGELOG](CHANGELOG.md#200---2026-09-05) — não vale a pena mantê-los aqui em
duplicidade. O que segue é o que **ainda não** foi feito.

Ambiente de referência: PHP 8.5, Laravel 13.17, Livewire 4.3.1, PHPUnit 12.
Estado atual: 57 testes / 120 asserções, PHPStan nível 5 sem erros, Pint limpo.

---

## Dívidas conhecidas da 2.0.0

Coisas que a 2.0.0 conscientemente **não** resolveu. Não são surpresas; são
escolhas registradas.

| Dívida | Onde | Consequência hoje |
| --- | --- | --- |
| Strings de UI em português, hardcoded | [ModalCrud.php:58-74](src/Livewire/ModalCrud.php#L58-L74) | Inutilizável fora do pt-BR sem sobrescrever 8 propriedades por modal |
| Só Bootstrap 4/5 | [resources/js/](resources/js/), views | Projetos Tailwind ficam de fora |
| Lista de adapters é uma constante fechada | [ModalAssets.php:24](src/ModalAssets.php#L24) | Ninguém consegue plugar um adapter próprio |
| Modais empilhados não existem | removido na 2.0.0 | Fluxos "criar cliente dentro do form de pedido" precisam de gambiarra |
| Limpeza de shells duplicados trata o sintoma | [core.js:111-122](resources/js/core.js#L111-L122) | Funciona, mas a causa (`wire:navigate` reaproveitando o DOM) segue lá |
| `authorizeModal()` é no-op por padrão | [ModalCrud.php:98](src/Livewire/ModalCrud.php#L98) | Seguro só para quem lê a documentação |
| `resolveModel()` sempre usa `findOrFail` | [ModalCrud.php:171](src/Livewire/ModalCrud.php#L171) | Sem soft deletes, sem chave de rota customizada, sem eager loading |
| Hooks resolvidos por `method_exists()` | [ModalCrud.php:369](src/Livewire/ModalCrud.php#L369) | Um typo em `stored()` falha em silêncio |

---

## 2.1 — sem breaking changes

### 2.1.1 Internacionalização — ALTO impacto

O item adiado na 2.0.0. Substituir os literais por chaves de tradução:

```php
$this->createTitle = __('modal-crud::modal.create');
```

Publicar `lang/en/modal.php` e `lang/pt_BR/modal.php` via
`loadTranslationsFrom()` e uma tag `modal-lang`. As propriedades públicas
continuam funcionando como override por componente.

**O detalhe que torna isso não-trivial:** trocar os defaults para inglês muda a
UI de quem já usa o pacote e roda com `app.locale = 'en'`. Para manter a 2.1
não-breaking:

```php
// config/modal-crud.php
'translate' => false, // 2.x: mantém os literais atuais
                      // 3.0: passa a true e a chave some
```

Com `false`, as propriedades recebem os mesmos textos de hoje. Com `true`, vão
para o tradutor. A 3.0 remove a flag e assume `true`.

**Esforço:** médio. **Risco:** baixo, com a flag.

### 2.1.2 Adapters registráveis — desbloqueia o resto

`ModalAssets::SCRIPTS` é uma `const` fechada, então a única forma de usar outra
biblioteca de modal hoje é dar fork no pacote. Abrir isso:

```php
// config/modal-crud.php
'adapters' => [
    'bootstrap5' => null,                              // embutido
    'bootstrap4' => null,                              // embutido
    'my-ui' => resource_path('js/my-modal-adapter.js'), // do usuário
],
```

`ModalAssets` passa a validar contra as chaves configuradas em vez da constante,
e o `AssetController` serve o caminho registrado. A allowlist continua existindo
— o que muda é quem a preenche.

Isso é pré-requisito real para 2.1.3: sem ele, cada nova UI exige um release do
pacote.

**Esforço:** pequeno. **Risco:** baixo — a superfície de segurança
(path traversal) continua coberta pela allowlist, agora dinâmica.

### 2.1.3 Driver Tailwind / Alpine — cumpre a promessa antiga do README

A separação `core.js` + adapter feita na 2.0.0 tornou o lado JS barato: um
adapter Tailwind é essencialmente `show`/`hide`/`onHidden` sobre uma transição
Alpine. O trabalho real está nas **views**, que hoje são Bootstrap puro
(`modal-content`, `btn-close`, `data-bs-dismiss`).

Proposta: namespacear as views por UI.

```
resources/views/bootstrap/components/layouts/modal.blade.php
resources/views/tailwind/components/layouts/modal.blade.php
```

`ModalCrud::viewDefault()` resolve `modal-crud::{ui}.components.layouts.modal`,
onde `{ui}` vem de uma nova config `'ui' => 'bootstrap'`. Os caminhos atuais
seguem funcionando via alias, como já é feito com `modalcrud::`.

**Esforço:** médio-alto (é o item mais caro da 2.1). **Risco:** médio — mexe na
resolução de views, que é exatamente onde estava o bug 1.2 da 2.0.0. Precisa do
mesmo tipo de teste estrutural que `PublishingTest` faz hoje.

### 2.1.4 `php artisan make:modal-crud`

```bash
php artisan make:modal-crud UsersForm --model=User --view --force
```

Gera o componente com `$modelClass`, `rules()` a partir das colunas, um
`authorizeModal()` já preenchido com `$this->authorize(...)` e a view do form.
O Livewire tem `LivewireMakeCommand`/`FileManipulationCommand` em
`SupportConsoleCommands` para servir de base.

O ganho maior não é conveniência: é que o *stub* coloca `authorizeModal()` na
frente de quem cria o modal, em vez de depender da leitura do README.

**Esforço:** médio. **Risco:** baixo.

### 2.1.5 Form objects: dois modos de falha silenciosa

Verificado empiricamente, não por leitura. Um modal com uma propriedade
`public UserForm $form` (`Livewire\Form`):

| Cenário | Resultado hoje |
| --- | --- |
| `create`, sem `$fillable` | ✅ funciona — grava corretamente |
| `create`, **com** `$fillable` | ❌ grava `title => null`, sem erro |
| `edit` | ❌ o form abre **em branco**, sem erro |

Ou seja: o caso mais simples funciona, o que faz os dois outros parecerem
suportados. Ambos falham em silêncio, que é o pior modo de falha possível.

**Causa do primeiro:** `persistableData()` chama `$this->only(['title'])`, que
procura uma propriedade `title` no *componente*. Ela não existe — está em
`$form->title` — então `only()` devolve `['title' => null]`.

**Causa do segundo:** `mount()` faz `$this->fill($this->model)`, que preenche
propriedades do componente. O form object não é tocado.

Correção: detectar `Livewire\Form` nas duas rotas.

```php
protected function persistableData(array $validated): array
{
    if ($form = $this->formObject()) {
        return $this->fillable === []
            ? $form->all()
            : Arr::only($form->all(), $this->fillable);
    }

    // ... comportamento atual
}
```

E, no `mount()`, encaminhar o `fill()` para o form quando houver um. Vale também
aceitar `#[Validate]` nas propriedades como alternativa a `rules()`.

**Esforço:** médio. **Risco:** baixo, é aditivo.
**Prioridade alta** — não é ausência de funcionalidade, é corrupção silenciosa
de dados num caminho que aparenta funcionar.

### 2.1.6 Resolução de model extensível

`resolveModel()` é rígido:

```php
$this->model = (new $this->modelClass)->findOrFail($this->id);
```

Sem soft deletes (não dá para fazer um modal de "restaurar"), sem chave de rota
customizada (`slug`), sem eager loading (N+1 garantido em views com relações).
Extrair um ponto de extensão:

```php
protected function resolveModelQuery(): Builder
{
    return $this->modelClass::query();
}
```

E o modal sobrescreve com `->withTrashed()`, `->with(['author', 'tags'])`, ou o
que precisar.

**Esforço:** pequeno. **Risco:** baixo.

### 2.1.7 Acessibilidade: devolver o foco ao gatilho

O modal é aberto por `Livewire.dispatchTo(...)`, não por `data-bs-toggle`, então
o Bootstrap não recebe `relatedTarget` e não sabe para onde devolver o foco ao
fechar. Hoje o foco volta para o `<body>`, o que é ruim para teclado e leitor de
tela.

`core.js` pode guardar `document.activeElement` no `openModalInBrowser` e
restaurá-lo no `onHidden`. Vale também `aria-describedby` apontando para o corpo
no modal de exclusão.

**Esforço:** pequeno. **Risco:** baixo.

### 2.1.8 CI: cobertura e mutação

O CI hoje só roda `phpunit` sem cobertura. Adicionar:

- um job com `coverage: xdebug` e limite mínimo (`--coverage-text` + fail-under);
- `infection/infection` — com 57 testes cobrindo lógica de decisão (`match` por
  modo, hooks, allowlists), teste de mutação encontra buracos que a cobertura de
  linha esconde.

**Esforço:** pequeno. **Risco:** nenhum.

---

## 2.2 — funcionalidades

### 2.2.1 Modais empilhados, desta vez implementados

Removidos na 2.0.0 porque a API existia e não fazia nada. Reintroduzir só vale a
pena com a implementação completa:

- `Modal::$components` volta a ser um array, com `$activeComponent`;
- a view renderiza N shells, cada um com sua `wire:key`;
- `z-index` e backdrop incrementais (Bootstrap não empilha backdrops sozinho);
- `skipPreviousModals()` / `destroySkippedModals()` voltam **com semântica
  real**, e o payload de `closeModalInBrowser` volta a carregar `skip`/`destroy`;
- `closeModalOnEscapeIsForceful()` volta a fazer sentido (Escape fecha a pilha
  inteira em vez de um nível).

Opt-in por config (`'stacked' => false`) para não alterar o comportamento de
quem já está na 2.x.

**Esforço:** alto — é o maior item do roadmap. **Risco:** alto, é frontend com
estado, difícil de cobrir só com teste de backend. Merece testes de browser
(Dusk ou Playwright) antes do merge.

### 2.2.2 Configuração por atributo PHP

Sete métodos estáticos para configurar um modal é muito cerimonial:

```php
#[Modal(size: 'modal-xl', centered: false, closeOnClickAway: false)]
class UsersForm extends ModalCrud {}
```

`Modal::openModal()` lê o atributo via reflection e cai nos métodos estáticos
quando ele não existe — então os dois estilos coexistem e nada quebra.

**Esforço:** médio. **Risco:** baixo.

### 2.2.3 Eventos Laravel

Além dos hooks de método (`stored()`, `changed()`, …), emitir eventos de
verdade: `ModalOpened`, `RecordStored`, `RecordChanged`, `RecordDestroyed`.
Permite listeners fora do componente — auditoria, cache, notificações — sem
sobrescrever hooks em cada modal.

**Esforço:** pequeno. **Risco:** baixo.

### 2.2.4 Lazy loading do conteúdo

Um form pesado hoje trava o clique enquanto monta. O Livewire 4 tem `#[Lazy]`;
basta o `ModalCrud` repassá-lo e o shell mostrar um placeholder — o modal abre
instantaneamente e o conteúdo chega depois.

```php
#[Lazy]
class HeavyReportModal extends ModalCrud {}
```

**Esforço:** pequeno (majoritariamente repasse + documentação).
**Risco:** baixo.

### 2.2.5 `@persist` no shell do modal

A limpeza de shells duplicados em `core.js::onNavigated()` trata sintoma. A
causa é `@livewire('modal')` estar no layout e o `wire:navigate` reaproveitar o
DOM. Envolver o shell em `@persist('modal-crud')` resolve na origem, e permite
apagar `onNavigated()`.

Precisa validar como o `@persist` interage com o componente aninhado sendo
destruído — daí não estar na 2.1.

**Esforço:** médio. **Risco:** médio; precisa de teste de navegação real.

---

## 3.0 — breaking changes

### 3.0.1 `authorizeModal()` seguro por padrão — o item mais importante

Hoje o default é no-op. Isso significa que um modal escrito por alguém que não
leu a seção de segurança do README fica exposto exatamente como na 1.x. O
default deveria falhar fechado.

Proposta:

- se o modal tem `$modelClass` **e** existe uma Policy registrada para esse
  model, `authorizeModal()` chama `$this->authorize($this->getMode(), $this->model)`
  automaticamente;
- se o modal tem `$modelClass` e **não** existe Policy, lança em ambiente local
  e loga em produção — silêncio nunca;
- opt-out explícito e legível: `protected bool $skipAuthorization = true;`.

**Caminho de migração:** a 2.x introduz o aviso de deprecação (log) para todo
modal com `$modelClass` que não sobrescreve `authorizeModal()`. Quem atualizar
para a 3.0 já terá visto o aviso.

**Esforço:** médio. **Risco:** alto por definição — é o ponto do roadmap que
mais exige comunicação clara no CHANGELOG.

### 3.0.2 Hooks tipados no lugar de `method_exists()`

```php
$method && method_exists($this, $method) && $this->{$method}();
```

Um typo em `stored()` não dá erro; simplesmente não roda. Substituir por
interfaces opcionais (`HandlesStored`, `HandlesDestroyed`) ou por um atributo
`#[OnStored]`, ambos verificáveis pelo PHPStan.

**Esforço:** médio. **Risco:** médio — muda a forma documentada de escrever
hooks.

### 3.0.3 Limpeza acumulada

- Tipar `$modelClass` como `?string` (impossível na 2.0 sem quebrar toda
  subclasse existente — ver o CHANGELOG).
- Remover o namespace de view legado `modalcrud::`.
- Renomear `emitEvent()`/`emitModalEvents()` — o Livewire abandonou "emit" em
  favor de "dispatch" na 3.0, e o pacote ficou com a nomenclatura antiga.
- Aceitar `ModalMode|string` em `$mode`, com o enum como caminho preferencial.
- Remover a flag `translate` da 2.1.1.

**Esforço:** pequeno cada. **Risco:** baixo individualmente; agrupar num único
release para não pedir migrações repetidas.

---

## Priorização sugerida

Ordenado por (impacto ÷ esforço), não por número de versão.

| # | Item | Versão | Esforço | Por quê agora |
| --- | --- | --- | --- | --- |
| 1 | Form objects (2.1.5) | 2.1 | Médio | Grava `null` e abre form vazio, sem erro nenhum |
| 2 | Adapters registráveis (2.1.2) | 2.1 | Pequeno | Desbloqueia Tailwind sem release do pacote |
| 3 | i18n (2.1.1) | 2.1 | Médio | Maior barreira de adoção fora do Brasil |
| 4 | `make:modal-crud` (2.1.4) | 2.1 | Médio | Coloca a autorização no caminho padrão |
| 5 | Resolução de model (2.1.6) | 2.1 | Pequeno | Destrava soft deletes e resolve N+1 |
| 6 | Foco/a11y (2.1.7) | 2.1 | Pequeno | Correção de acessibilidade barata |
| 7 | Autorização por padrão (3.0.1) | 3.0 | Médio | Precisa do aviso de deprecação já na 2.1 |
| 8 | Driver Tailwind (2.1.3) | 2.1 | Alto | Depende do #2 |
| 9 | Cobertura + mutação (2.1.8) | 2.1 | Pequeno | Protege tudo acima |
| 10 | Atributo `#[Modal]` (2.2.2) | 2.2 | Médio | Ergonomia |
| 11 | Eventos Laravel (2.2.3) | 2.2 | Pequeno | Ergonomia |
| 12 | Lazy loading (2.2.4) | 2.2 | Pequeno | Performance percebida |
| 13 | `@persist` (2.2.5) | 2.2 | Médio | Remove uma gambiarra |
| 14 | Modais empilhados (2.2.1) | 2.2 | Alto | Maior feature; exige teste de browser |
| 15 | Limpeza 3.0 (3.0.2, 3.0.3) | 3.0 | Médio | Agrupar num release só |

**Sugestão de recorte:** 2.1 = itens 1–6 e 9 (o 8 sai se o prazo apertar);
2.2 = 10–14; 3.0 = 7 e 15, com o aviso de deprecação do 7 embarcado já na 2.1.

---

## Pré-requisito para qualquer coisa acima

O CI ainda não rodou de verdade — a matriz de PHP 8.2–8.4 × Laravel 11–13 foi
escrita junto com a 2.0.0, mas a validação local aconteceu só em PHP 8.5 /
Laravel 13.17. **Antes de abrir qualquer frente do roadmap, confirmar que a
matriz passa inteira.** Os pontos mais prováveis de falhar são as combinações
`--prefer-lowest` com Laravel 11, onde a API de `Testbench` 9 difere.
