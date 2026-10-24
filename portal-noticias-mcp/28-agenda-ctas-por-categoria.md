# Agenda 73 — CTAs por categoria e destaque amarelo

Aplicado na Home e no guia Agenda 73, mantendo foto, título e botão ligados à mesma URL oficial (com alternativa no link de ingressos/inscrição).

| Categoria | CTA |
| --- | --- |
| Shows & Festas | Ver atrações e ingressos → |
| Gastronomia & Feiras | Explorar a programação → |
| Esportes | Ver programação esportiva → |
| Teatro & Cultura | Ver programação cultural → |
| Negócios & Cursos | Ver detalhes e inscrição → |
| Infantil & Família | Planejar meu passeio → |

Shows usam **Ver atrações →** e Negócios usam **Ver programação →** quando não houver link de ingressos/inscrição cadastrado, quando a oferta estiver esgotada/encerrada ou quando o card for modelo. A existência desse link é a informação fornecida pelo administrador; cadastre-o apenas após confirmar a venda/inscrição. Não há consulta automática de estoque em sites externos.

Botão com fundo `#FFBE0B`, texto `#2C1067` em negrito, hover `#FFEA02`, largura completa, altura mínima de 48px e cantos de 6px. Estado desativado mantém texto roxo e usa amarelo claro `#FFF1C2`, borda ouro e ausência de ação. Modelos e eventos sem URL válida continuam desativados.

Implementação em `implementacao-wp/agenda-eventos.php` (snippet 11, prioridade 15) e `implementacao-wp/css-g1-techtudo-ultra-leve.css`. Nenhum script ou plugin novo no frontend.

Validação de oito cenários em rascunho privado: seis categorias, além das alternativas informativas de Shows e Negócios. Rascunho excluído ao finalizar. Verificados navegação, filtros, cores normal/hover e altura do botão em telas responsivas de desktop e mobile; nenhum destino externo recebeu mensagem ou compra. LiteSpeed Cache limpo após implantação.
