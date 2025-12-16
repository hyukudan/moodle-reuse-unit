<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for local_reuseunit (Brazilian Portuguese).
 *
 * @package    local_reuseunit
 * @copyright  2024 hyukudan
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

// General.
$string['pluginname'] = 'Reutilizar Unidade';
$string['plugindescription'] = 'Importar seções/unidades de outros cursos com todas as suas atividades e recursos.';

// Capabilities.
$string['reuseunit:import'] = 'Importar unidades de outros cursos';
$string['reuseunit:export'] = 'Permitir exportar/compartilhar unidades';
$string['reuseunit:managetemplates'] = 'Gerenciar modelos globais de unidades';

// Navigation and actions.
$string['importunit'] = 'Importar unidade';
$string['importunithere'] = 'Importar unidade aqui';
$string['importfromcourse'] = 'Importar de outro curso';

// Wizard steps.
$string['step1_title'] = 'Selecionar origem';
$string['step1_description'] = 'Escolha o curso e a seção que você deseja importar.';
$string['step2_title'] = 'Visualização e opções';
$string['step2_description'] = 'Revise o conteúdo e configure as opções de importação.';
$string['step3_title'] = 'Selecionar destino';
$string['step3_description'] = 'Escolha onde colocar a seção importada.';

// Source selection.
$string['sourcecourse'] = 'Curso de origem';
$string['selectsourcecourse'] = 'Selecionar curso de origem';
$string['searchcourse'] = 'Buscar curso...';
$string['searchcourseplaceholder'] = 'Digite para buscar cursos...';
$string['nocoursesfound'] = 'Nenhum curso encontrado';
$string['selectsection'] = 'Selecionar seção';
$string['availablesections'] = 'Seções disponíveis';
$string['nosections'] = 'Este curso não tem seções';
$string['sectioncontent'] = '{$a->activities} atividades, {$a->resources} recursos';

// Preview.
$string['preview'] = 'Visualização';
$string['contenttobeimported'] = 'Conteúdo a ser importado';
$string['activities'] = 'atividades';
$string['resources'] = 'recursos';
$string['activity'] = 'atividade';
$string['resource'] = 'recurso';
$string['emptysection'] = 'Esta seção está vazia';

// Options.
$string['importoptions'] = 'Opções de importação';
$string['options'] = 'Opções';
$string['resetdates'] = 'Redefinir datas';
$string['resetdates_desc'] = 'Redefinir todas as datas das atividades em relação à data de início do novo curso.';
$string['includerestrictions'] = 'Incluir restrições de acesso';
$string['includerestrictions_desc'] = 'Importar as restrições de acesso configuradas nas atividades.';
$string['includegradebook'] = 'Incluir estrutura do livro de notas';
$string['includegradebook_desc'] = 'Importar categorias e configurações do livro de notas (não as notas dos alunos).';
$string['includegroups'] = 'Incluir restrições de grupo';
$string['includegroups_desc'] = 'Importar restrições baseadas em grupos (os grupos devem existir no destino).';

// Destination selection.
$string['destinationcourse'] = 'Curso de destino';
$string['selectdestination'] = 'Selecionar destino';
$string['currentcourse'] = 'Curso atual';
$string['insertposition'] = 'Posição de inserção';
$string['position_start'] = 'No início do curso';
$string['position_end'] = 'No final do curso';
$string['position_after'] = 'Após a seção:';
$string['newsectionname'] = 'Novo nome da seção (opcional)';
$string['keepsectionname'] = 'Manter nome original';

// Import process.
$string['import'] = 'Importar';
$string['importing'] = 'Importando...';
$string['importprogress'] = 'Progresso da importação';
$string['preparingimport'] = 'Preparando importação...';
$string['creatingbackup'] = 'Criando backup da seção de origem...';
$string['restoringcontent'] = 'Restaurando conteúdo no destino...';
$string['finalizingimport'] = 'Finalizando importação...';

// Results.
$string['importsuccessful'] = 'Importação bem-sucedida';
$string['importcompleted'] = 'A seção foi importada com sucesso.';
$string['importedactivities'] = '{$a} atividades importadas';
$string['importedresources'] = '{$a} recursos importados';
$string['viewimportedsection'] = 'Ver seção importada';
$string['importanother'] = 'Importar outra unidade';

// Errors.
$string['importfailed'] = 'Falha na importação';
$string['error_nocourseaccess'] = 'Você não tem acesso a este curso.';
$string['error_nosectionaccess'] = 'Você não tem acesso a esta seção.';
$string['error_sectionnotfound'] = 'Seção não encontrada.';
$string['error_coursenotfound'] = 'Curso não encontrado.';
$string['error_backupfailed'] = 'Falha ao criar backup da seção de origem.';
$string['error_restorefailed'] = 'Falha ao restaurar conteúdo no destino.';
$string['error_nopermission'] = 'Você não tem permissão para realizar esta ação.';
$string['error_invalidsection'] = 'Seção selecionada inválida.';
$string['error_invalidcourse'] = 'Curso selecionado inválido.';

// Confirmation.
$string['confirmmimport'] = 'Confirmar importação';
$string['confirmimportmessage'] = 'Você está prestes a importar o seguinte conteúdo:';
$string['importwarning'] = 'Esta ação não pode ser desfeita. Certifique-se de ter selecionado a seção correta.';

// Settings.
$string['defaultoptions'] = 'Opções padrão de importação';
$string['defaultoptions_desc'] = 'Configure as opções padrão para importar unidades.';
$string['maxsections'] = 'Máximo de seções';
$string['maxsections_desc'] = 'Número máximo de seções que podem ser importadas de uma vez.';

// History.
$string['importhistory'] = 'Histórico de importações';
$string['recentimports'] = 'Importações recentes';
$string['nohistory'] = 'Sem histórico de importações';
$string['importedon'] = 'Importado em {$a}';
$string['importedby'] = 'Importado por {$a}';

// Favorites.
$string['favorites'] = 'Favoritos';
$string['addtofavorites'] = 'Adicionar aos favoritos';
$string['removefromfavorites'] = 'Remover dos favoritos';
$string['nofavorites'] = 'Sem modelos favoritos';
$string['favoriteadded'] = 'Adicionado aos favoritos';
$string['favoriteremoved'] = 'Removido dos favoritos';

// Navigation.
$string['next'] = 'Próximo';
$string['previous'] = 'Anterior';
$string['cancel'] = 'Cancelar';
$string['close'] = 'Fechar';
$string['back'] = 'Voltar';

// Misc.
$string['loading'] = 'Carregando...';
$string['section'] = 'Seção';
$string['course'] = 'Curso';
$string['category'] = 'Categoria';

// Templates.
$string['templates'] = 'Modelos';
$string['searchtemplates'] = 'Buscar modelos...';
$string['notemplates'] = 'Nenhum modelo disponível';
$string['saveastemplate'] = 'Salvar como modelo';
$string['savetemplate'] = 'Salvar modelo';
$string['templatesaved'] = 'Modelo salvo com sucesso';
$string['templatedeleted'] = 'Modelo excluído com sucesso';
$string['templatename'] = 'Nome do modelo';
$string['templatedescription'] = 'Descrição';
$string['templatedescription_placeholder'] = 'Descreva o que este modelo contém...';
$string['templatetags'] = 'Tags';
$string['templatetags_placeholder'] = 'matemática, álgebra, iniciante';
$string['templatetags_help'] = 'Separe as tags com vírgulas';

// Share levels.
$string['sharelevel'] = 'Compartilhar com';
$string['sharelevel_personal'] = 'Apenas eu';
$string['sharelevel_personal_desc'] = 'Somente você pode ver e usar este modelo';
$string['sharelevel_category'] = 'Meu departamento';
$string['sharelevel_category_desc'] = 'Professores da mesma categoria podem usar este modelo';
$string['sharelevel_global'] = 'Toda a instituição';
$string['sharelevel_global_desc'] = 'Todos os professores do site podem usar este modelo';

// Template management.
$string['managetemplates'] = 'Gerenciar modelos';
$string['mytemplates'] = 'Meus modelos';
$string['sharedtemplates'] = 'Modelos compartilhados';
$string['globaltemplates'] = 'Modelos globais';
$string['templateusage'] = 'Usado {$a} vezes';
$string['updatetemplate'] = 'Atualizar modelo';
$string['deletetemplate'] = 'Excluir modelo';
$string['confirmdeletetemplate'] = 'Tem certeza de que deseja excluir este modelo?';

// Duplicate section.
$string['duplicatesection'] = 'Duplicar seção';
$string['duplicating'] = 'Duplicando...';
$string['duplicatesuccessful'] = 'Seção duplicada com sucesso';
$string['duplicateposition'] = 'Posicionamento';
$string['position_after_source'] = 'Após a seção original';
$string['copy'] = 'cópia';

// Export section.
$string['exportsection'] = 'Exportar seção';
$string['exportasmbz'] = 'Exportar como .mbz';
$string['exporting'] = 'Exportando...';
$string['exportsuccessful'] = 'Exportação bem-sucedida';
$string['downloadbackup'] = 'Baixar backup';
$string['exportfilename'] = 'Nome do arquivo de exportação';
$string['exportfilename_help'] = 'Deixe vazio para gerar automaticamente';

// Search sections.
$string['searchsections'] = 'Buscar seções';
$string['searchbyactivity'] = 'Buscar por tipo de atividade';
$string['activitytypes'] = 'Tipos de atividade';
$string['filterbyactivity'] = 'Filtrar por tipo de atividade';
$string['selectactivitytypes'] = 'Selecione tipos de atividade...';
$string['allactivities'] = 'Todas as atividades';
$string['sectionsmatching'] = '{$a} seções encontradas';
$string['nosectionsfound'] = 'Nenhuma seção corresponde aos critérios';
$string['searchplaceholder'] = 'Buscar pelo nome da seção...';
$string['advancedsearch'] = 'Busca avançada';
$string['clearfilters'] = 'Limpar filtros';

// Batch import.
$string['batchimport'] = 'Importação em lote';
$string['batchimportdesc'] = 'Importar múltiplas seções de uma vez';
$string['selectmultiple'] = 'Selecionar múltiplas seções';
$string['selectedsections'] = 'Seções selecionadas';
$string['nosectionsselected'] = 'Nenhuma seção selecionada';
$string['batchimporting'] = 'Importando seções...';
$string['batchimportcomplete'] = '{$a->success} de {$a->total} seções importadas com sucesso';
$string['batchimportfailed'] = 'Algumas importações falharam. Verifique os resultados individuais.';
$string['addtoqueue'] = 'Adicionar à fila';
$string['removefromqueue'] = 'Remover da fila';
$string['clearqueue'] = 'Limpar tudo';
$string['importqueue'] = 'Fila de importação';
$string['queueitems'] = '{$a} itens na fila';
$string['error_toomanyimports'] = 'Máximo de {$a} seções podem ser importadas de uma vez';
$string['processingitem'] = 'Processando {$a->current} de {$a->total}...';

// Template versioning.
$string['versions'] = 'Versões';
$string['versionhistory'] = 'Histórico de versões';
$string['currentversion'] = 'Versão atual';
$string['version'] = 'Versão {$a}';
$string['versioncreated'] = 'Versão {$a} criada com sucesso';
$string['createversion'] = 'Criar nova versão';
$string['updatetemplate'] = 'Atualizar modelo';
$string['changelog'] = 'Descrição da alteração';
$string['changelog_help'] = 'Descreva o que mudou nesta versão';
$string['noversions'] = 'Nenhum histórico de versões disponível';
$string['viewversions'] = 'Ver versões';
$string['restoreversion'] = 'Restaurar esta versão';
$string['confirmrestore'] = 'Tem certeza de que deseja restaurar a versão {$a}?';
$string['versionrestored'] = 'Versão restaurada com sucesso';
$string['compareversions'] = 'Comparar versões';
