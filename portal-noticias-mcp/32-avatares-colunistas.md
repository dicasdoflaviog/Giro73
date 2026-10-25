# Avatares dos seis perfis editoriais — 25/10/2026

As seis imagens adicionadas pelo administrador à Biblioteca de Mídia foram associadas pelo nome aos perfis existentes. Os nomes, as biografias, as colunas e a responsabilidade editorial da Redação Giro73 foram preservados.

| Perfil | Coluna | Perfil WP | Mídia nova | Artigo sincronizado |
|---|---|---:|---:|---:|
| Arthur Valente | Bússola do Bolso | 174 | 300 | 208 |
| Helena Gusmão | Radar Político & Gestão | 175 | 297 | 209 |
| Lucas Vianna | Futuro Prático | 176 | 298 | 210 |
| Beto Santana | Arquibancada & Resenha | 177 | 295 | 211 |
| Malu Santoro | Veneno & VIP | 178 | 299 | 212 |
| Clara Mendes | Raiz & Vitalidade | 179 | 296 | 213 |

## Aplicação e performance

- Atualizado `g73_columnist_avatar` nos seis CPTs `g73_columnist` e nos seis artigos vinculados.
- ALT das novas mídias: `Avatar editorial de <nome> — <coluna>`. Os arquivos originais PNG foram mantidos na biblioteca.
- O renderer `g73_reg_avatar` usa a miniatura nativa `thumbnail`, `sizes: 64px`, largura/altura 64, lazy loading e a mesma apresentação circular existente. Evita escolher o PNG original de 1254×1254 como fonte padrão.
- As miniaturas nativas de 150×150 foram copiadas para `implementacao-wp/autoridade-assets/perfis/`. Os seis arquivos verificados somam 112.035 bytes; o navegador ainda pode selecionar outra resolução do srcset conforme sua densidade de tela.
- O Snippet 10 foi atualizado, reativado e conferido contra `implementacao-wp/autoridade-regional.php`; prioridade 14 e sem erro de código. As mídias e os avatares anteriores foram preservados para histórico/rollback.
- Cache de páginas LiteSpeed limpo. As atualizações de metadados refletiram a modificação dos artigos no WordPress; os textos não foram editados.

## Verificação

- Home HTTP 200 e seis tags de avatar com os novos arquivos e tamanho responsivo de 64 px.
- Endpoint `/wp-json/g73/v1/columnists` retorna os seis IDs de avatar novos.
- Preview editorial das seis colunas usa os perfis novos; artigo público 208 também foi conferido com o avatar atualizado.
- Os seis arquivos de miniatura retornaram HTTP 200 e foram validados como PNG 150×150.
- PHP do renderer aprovado na verificação de sintaxe. Nenhum plugin adicional foi instalado.

Mapeamento, URLs de origem, arquivos locais e resultados: `implementacao-wp/avatares-colunistas.json`.
