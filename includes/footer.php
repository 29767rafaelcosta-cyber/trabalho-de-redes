    </main>

    <!-- Rodapé Turbo & Pagamentos em Destaque -->
    <footer class="bg-gray-950 text-gray-400 mt-20 pt-16 border-t border-gray-800">
        
        <!-- Faixa de Pagamentos & Confiança -->
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-12 border-b border-gray-800/80">
            <div class="bg-gradient-to-r from-gray-900 via-gray-800 to-gray-900 p-6 rounded-3xl border border-gray-800 flex flex-col md:flex-row items-center justify-between gap-6 shadow-2xl">
                <div>
                    <span class="text-orange-500 text-xs font-black uppercase tracking-wider block">Checkout 100% Protegido</span>
                    <h3 class="text-lg font-black text-white">Métodos de Pagamento Rápidos & Aceites</h3>
                    <p class="text-xs text-gray-400 mt-0.5">Pague em segurança com confirmação na hora ou divida em 3x sem juros.</p>
                </div>

                <!-- Crachás Coloridos de Pagamentos -->
                <div class="flex flex-wrap items-center gap-3">
                    <div class="bg-gray-950 px-3 py-2 rounded-2xl border border-gray-700 flex items-center space-x-2 text-xs font-black text-white shadow-md">
                        <i class="fa-solid fa-mobile-screen text-green-400 text-base"></i>
                        <span>MB WAY</span>
                    </div>

                    <div class="bg-gray-950 px-3 py-2 rounded-2xl border border-gray-700 flex items-center space-x-2 text-xs font-black text-white shadow-md">
                        <i class="fa-solid fa-receipt text-yellow-400 text-base"></i>
                        <span>Multibanco</span>
                    </div>

                    <div class="bg-gray-950 px-3 py-2 rounded-2xl border border-gray-700 flex items-center space-x-2 text-xs font-black text-white shadow-md">
                        <i class="fa-brands fa-cc-visa text-blue-400 text-base"></i>
                        <i class="fa-brands fa-cc-mastercard text-red-400 text-base"></i>
                        <span>Cartão</span>
                    </div>

                    <div class="bg-gray-950 px-3 py-2 rounded-2xl border border-gray-700 flex items-center space-x-2 text-xs font-black text-white shadow-md">
                        <i class="fa-brands fa-paypal text-blue-500 text-base"></i>
                        <span>PayPal</span>
                    </div>

                    <div class="bg-gradient-to-r from-pink-600 to-purple-600 px-3 py-2 rounded-2xl text-xs font-black text-white shadow-md">
                        <span>Klarna 3x</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <!-- Coluna 1: Marca & Slogan -->
                <div>
                    <div class="flex items-center space-x-3 mb-4">
                        <div class="w-10 h-10 rounded-xl bg-gradient-to-r from-orange-500 to-red-600 flex items-center justify-center text-white shadow-md">
                            <i class="fa-solid fa-fire text-lg text-yellow-300"></i>
                        </div>
                        <span class="text-xl font-black text-white">Auto<span class="text-orange-500">Peças</span> TURBO</span>
                    </div>
                    <p class="text-xs text-gray-400 mb-4 leading-relaxed">
                        A tua loja online de peças automóveis de alto rendimento. Discos de travão, óleos, filtros e kits de distribuição com envio expresso em 24h.
                    </p>
                    <div class="flex space-x-3 text-sm">
                        <a href="#" class="w-8 h-8 rounded-xl bg-gray-800 hover:bg-orange-600 hover:text-white flex items-center justify-center transition"><i class="fa-brands fa-instagram"></i></a>
                        <a href="#" class="w-8 h-8 rounded-xl bg-gray-800 hover:bg-orange-600 hover:text-white flex items-center justify-center transition"><i class="fa-brands fa-facebook-f"></i></a>
                        <a href="#" class="w-8 h-8 rounded-xl bg-gray-800 hover:bg-orange-600 hover:text-white flex items-center justify-center transition"><i class="fa-brands fa-tiktok"></i></a>
                    </div>
                </div>

                <!-- Coluna 2: Categorias Populares -->
                <div>
                    <h4 class="text-xs font-black text-white uppercase tracking-wider mb-4 flex items-center text-orange-400">
                        <i class="fa-solid fa-gauge mr-2"></i> Peças Mais Procuradas
                    </h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= url('products.php?categoria=travoes') ?>" class="hover:text-orange-400 transition">&bull; Discos e Pastilhas Brembo</a></li>
                        <li><a href="<?= url('products.php?categoria=oleos-fluidos') ?>" class="hover:text-orange-400 transition">&bull; Óleos Castrol EDGE 5W-30</a></li>
                        <li><a href="<?= url('products.php?categoria=motor-distribuicao') ?>" class="hover:text-orange-400 transition">&bull; Kits Distribuição Continental</a></li>
                        <li><a href="<?= url('products.php?categoria=suspensao-direcao') ?>" class="hover:text-orange-400 transition">&bull; Amortecedores a Gás Bilstein</a></li>
                        <li><a href="<?= url('products.php?categoria=filtros') ?>" class="hover:text-orange-400 transition">&bull; Kits 4 Filtros Bosch</a></li>
                    </ul>
                </div>

                <!-- Coluna 3: Links de Ajuda & Notícias -->
                <div>
                    <h4 class="text-xs font-black text-white uppercase tracking-wider mb-4 flex items-center text-orange-400">
                        <i class="fa-solid fa-wrench mr-2"></i> Dicas & Apoio
                    </h4>
                    <ul class="space-y-2 text-xs">
                        <li><a href="<?= url('news.php') ?>" class="hover:text-orange-400 transition">&bull; Dicas de Manutenção & Óleos</a></li>
                        <li><a href="<?= url('products.php') ?>" class="hover:text-orange-400 transition">&bull; Catálogo Completo de Peças</a></li>
                        <li><a href="<?= url('profile.php') ?>" class="hover:text-orange-400 transition">&bull; As Minhas Encomendas & Recibos</a></li>
                        <li><a href="<?= url('login.php') ?>" class="hover:text-orange-400 transition">&bull; Acesso Restrito Backoffice</a></li>
                    </ul>
                </div>

                <!-- Coluna 4: Contacto Direto -->
                <div>
                    <h4 class="text-xs font-black text-white uppercase tracking-wider mb-4 flex items-center text-orange-400">
                        <i class="fa-solid fa-headset mr-2"></i> Apoio Imediato
                    </h4>
                    <div class="space-y-2 text-xs">
                        <p class="flex items-center text-white font-bold"><i class="fa-brands fa-whatsapp text-green-400 text-sm mr-2"></i> WhatsApp: +351 912 345 678</p>
                        <p><i class="fa-solid fa-envelope text-orange-400 mr-2"></i> suporte@autopecasturbo.pt</p>
                        <p class="text-gray-500 pt-1">Atendimento de Segunda a Sexta: 08:30 às 18:30</p>
                    </div>
                </div>

            </div>

            <!-- Rodapé Copyright -->
            <div class="mt-12 pt-6 border-t border-gray-800 text-xs text-gray-500 flex flex-col sm:flex-row items-center justify-between gap-2">
                <p>&copy; <?= date('Y') ?> AutoPeças TURBO. Todos os direitos reservados. Projeto Académico Full-Stack em PHP & MySQL / SQLite.</p>
                <div class="flex items-center space-x-3 text-[11px] text-gray-400">
                    <span class="text-green-400 font-bold"><i class="fa-solid fa-lock mr-1"></i> SSL 256-Bit</span>
                    <span>&bull;</span>
                    <span>IVA 23% Incluído</span>
                </div>
            </div>
        </div>

    </footer>

    <!-- Scripts -->
    <script src="<?= url('assets/js/app.js') ?>"></script>
</body>
</html>
