-- =====================================================================
-- Esquema + seed para Nativos Launchpad (Supabase / Postgres)
-- Ejecutar en: Supabase Dashboard → SQL Editor → New query
-- =====================================================================

-- Limpieza (opcional, descomentar si querés re-correr desde cero)
-- DROP TABLE IF EXISTS public.products      CASCADE;
-- DROP TABLE IF EXISTS public.testimonials  CASCADE;
-- DROP TABLE IF EXISTS public.tech_stack    CASCADE;

-- ---------------------------------------------------------------------
-- Tablas
-- ---------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS public.products (
    id              text        PRIMARY KEY,
    title           text        NOT NULL,
    category        text        NOT NULL,
    description     text        NOT NULL DEFAULT '',
    price           text        NOT NULL DEFAULT '',
    image           text        NOT NULL DEFAULT '',
    stats           jsonb       NOT NULL DEFAULT '[]'::jsonb,
    details         jsonb       NOT NULL DEFAULT '[]'::jsonb,
    included        jsonb       NOT NULL DEFAULT '[]'::jsonb,
    is_verified     boolean     NOT NULL DEFAULT false,
    security_rating numeric(3,1),
    seller_verified boolean     NOT NULL DEFAULT false,
    sort_order      int         NOT NULL DEFAULT 0,
    created_at      timestamptz NOT NULL DEFAULT now()
);

CREATE INDEX IF NOT EXISTS products_category_idx   ON public.products (category);
CREATE INDEX IF NOT EXISTS products_is_verified_idx ON public.products (is_verified);

CREATE TABLE IF NOT EXISTS public.testimonials (
    id          serial      PRIMARY KEY,
    name        text        NOT NULL,
    role        text        NOT NULL DEFAULT '',
    image       text        NOT NULL DEFAULT '',
    quote       text        NOT NULL DEFAULT '',
    sort_order  int         NOT NULL DEFAULT 0,
    created_at  timestamptz NOT NULL DEFAULT now()
);

CREATE TABLE IF NOT EXISTS public.tech_stack (
    id          serial      PRIMARY KEY,
    name        text        NOT NULL UNIQUE,
    sort_order  int         NOT NULL DEFAULT 0
);

-- ---------------------------------------------------------------------
-- RLS: lectura pública, escritura solo con service_role
-- ---------------------------------------------------------------------
ALTER TABLE public.products     ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.testimonials ENABLE ROW LEVEL SECURITY;
ALTER TABLE public.tech_stack   ENABLE ROW LEVEL SECURITY;

DROP POLICY IF EXISTS "public read products"     ON public.products;
DROP POLICY IF EXISTS "public read testimonials" ON public.testimonials;
DROP POLICY IF EXISTS "public read tech_stack"   ON public.tech_stack;

CREATE POLICY "public read products"     ON public.products     FOR SELECT USING (true);
CREATE POLICY "public read testimonials" ON public.testimonials FOR SELECT USING (true);
CREATE POLICY "public read tech_stack"   ON public.tech_stack   FOR SELECT USING (true);
-- (Las escrituras quedan bloqueadas para anon; con service_role se saltea RLS.)

-- =====================================================================
-- Seed: 15 productos
-- =====================================================================
INSERT INTO public.products
    (id, title, category, description, price, image, stats, details, included, is_verified, security_rating, seller_verified, sort_order)
VALUES
('menu-ai','MenuAI','SaaS para gastronomía con IA',
 'Plataforma revolucionaria que optimiza la gestión de pedidos y menús mediante inteligencia artificial predictiva.',
 '$45,000 USD',
 'https://images.unsplash.com/photo-1556742049-02e49f40b39a?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"12k+"},{"label":"Crecimiento mercado","value":"18%"},{"label":"Rating esperado","value":"4.8"}]',
 '[{"label":"Mercado","value":"Gastronomía","icon":"globe"},{"label":"Modelo","value":"SaaS B2B","icon":"layout"},{"label":"Retorno","value":"10-12 meses","icon":"trending-up"},{"label":"Tech","value":"Next.js + Python","icon":"database"}]',
 '["Full source code","DB ready","Admin panel","Support"]',
 true, 4.8, true, 10),

('technova','TechNova','Marketplace B2B de servicios tecnológicos',
 'Conectamos empresas con los mejores talentos y soluciones tecnológicas en un entorno seguro y escalable.',
 '$70,000 USD',
 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"25k+"},{"label":"Crecimiento mercado","value":"32%"},{"label":"Rating esperado","value":"4.9"}]',
 '[{"label":"Mercado","value":"Tech Services","icon":"globe"},{"label":"Modelo","value":"Marketplace","icon":"layout"},{"label":"Retorno","value":"14-18 meses","icon":"trending-up"},{"label":"Tech","value":"React + Node.js","icon":"database"}]',
 '["Full source code","Stripe integration","Verified vendors","Support"]',
 true, 4.9, true, 20),

('petprime','PetPrime','E-commerce premium para mascotas',
 'Tienda online de alta gama con suscripciones personalizadas para el cuidado integral de mascotas.',
 '$55,000 USD',
 'https://images.unsplash.com/photo-1450778869180-41d0601e046e?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"15k+"},{"label":"Crecimiento mercado","value":"25%"},{"label":"Rating esperado","value":"4.9"}]',
 '[{"label":"Mercado","value":"Pet Care","icon":"globe"},{"label":"Modelo","value":"E-commerce","icon":"layout"},{"label":"Retorno","value":"12-14 meses","icon":"trending-up"},{"label":"Tech","value":"Next.js + Shopify","icon":"database"}]',
 '["Full source code","Inventory system","Marketing kit","Support"]',
 true, 4.7, true, 30),

('toori360','Toori360','PropTech de Élite',
 'Desarrollado para inversores visionarios, Toori360 redefinirá el sector inmobiliario con una plataforma SaaS B2B de vanguardia. Con tecnología Next.js y NestJS, ofrece una solución escalable y segura para la gestión de activos, análisis predictivo y experiencias de usuario premium.',
 '$4,200 USD',
 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"15k+"},{"label":"Crecimiento mercado","value":"25%"},{"label":"Rating esperado","value":"4.9"}]',
 '[{"label":"Mercado","value":"Real Estate","icon":"globe"},{"label":"Modelo","value":"SaaS B2B","icon":"layout"},{"label":"Retorno","value":"12-14 meses","icon":"trending-up"},{"label":"Tech","value":"Next.js + NestJS","icon":"database"}]',
 '["Full source code","DB ready","Admin panel","Support"]',
 false, null, false, 40),

('healthsync','HealthSync','SaaS de Telemedicina',
 'Plataforma integral para clínicas y doctores que automatiza la agenda y consultas virtuales.',
 '$38,000 USD',
 'https://images.unsplash.com/photo-1576091160550-2173dba999ef?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"8k+"},{"label":"Crecimiento mercado","value":"22%"},{"label":"Rating esperado","value":"4.7"}]',
 '[{"label":"Mercado","value":"Healthcare","icon":"globe"},{"label":"Modelo","value":"SaaS B2B","icon":"layout"},{"label":"Retorno","value":"12-15 meses","icon":"trending-up"},{"label":"Tech","value":"React + Firebase","icon":"database"}]',
 '["Full source code","HIPAA compliant","Admin panel","Support"]',
 false, null, false, 50),

('eduflow','EduFlow','LMS para Academias Online',
 'Sistema de gestión de aprendizaje optimizado para la venta de cursos y seguimiento de alumnos.',
 '$25,000 USD',
 'https://images.unsplash.com/photo-1501504905252-473c47e087f8?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"20k+"},{"label":"Crecimiento mercado","value":"15%"},{"label":"Rating esperado","value":"4.6"}]',
 '[{"label":"Mercado","value":"Education","icon":"globe"},{"label":"Modelo","value":"SaaS","icon":"layout"},{"label":"Retorno","value":"8-10 meses","icon":"trending-up"},{"label":"Tech","value":"Next.js + PostgreSQL","icon":"database"}]',
 '["Full source code","Video hosting","Admin panel","Support"]',
 false, null, false, 60),

('cryptotrack','CryptoTrack','Dashboard de Inversiones Crypto',
 'Herramienta avanzada para el seguimiento de portafolios multi-chain en tiempo real.',
 '$12,000 USD',
 'https://images.unsplash.com/photo-1621761191319-c6fb62004040?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"50k+"},{"label":"Crecimiento mercado","value":"40%"},{"label":"Rating esperado","value":"4.8"}]',
 '[{"label":"Mercado","value":"Fintech","icon":"globe"},{"label":"Modelo","value":"Freemium","icon":"layout"},{"label":"Retorno","value":"6-8 meses","icon":"trending-up"},{"label":"Tech","value":"Vue + Go","icon":"database"}]',
 '["Full source code","API integrations","Admin panel","Support"]',
 false, null, false, 70),

('logismart','LogiSmart','Gestión de Logística Last-Mile',
 'Optimización de rutas y seguimiento de entregas para pequeñas y medianas empresas de transporte.',
 '$52,000 USD',
 'https://images.unsplash.com/photo-1586528116311-ad8dd3c8310d?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"5k+"},{"label":"Crecimiento mercado","value":"12%"},{"label":"Rating esperado","value":"4.5"}]',
 '[{"label":"Mercado","value":"Logistics","icon":"globe"},{"label":"Modelo","value":"SaaS B2B","icon":"layout"},{"label":"Retorno","value":"15-18 meses","icon":"trending-up"},{"label":"Tech","value":"React Native + Node.js","icon":"database"}]',
 '["Full source code","Mobile apps","Admin panel","Support"]',
 true, 4.8, false, 80),

('fitpulse','FitPulse','App de Entrenamiento Personalizado',
 'Plataforma para entrenadores que permite crear rutinas y planes nutricionales a medida.',
 '$18,000 USD',
 'https://images.unsplash.com/photo-1517836357463-d25dfeac3438?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"30k+"},{"label":"Crecimiento mercado","value":"20%"},{"label":"Rating esperado","value":"4.7"}]',
 '[{"label":"Mercado","value":"Wellness","icon":"globe"},{"label":"Modelo","value":"SaaS B2C","icon":"layout"},{"label":"Retorno","value":"10-12 meses","icon":"trending-up"},{"label":"Tech","value":"Flutter + Supabase","icon":"database"}]',
 '["Full source code","Mobile apps","Admin panel","Support"]',
 true, 4.5, false, 90),

('legalbot','LegalBot','Automatización de Contratos con IA',
 'Generador inteligente de documentos legales para startups y freelancers.',
 '$28,000 USD',
 'https://images.unsplash.com/photo-1589829545856-d10d557cf95f?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"10k+"},{"label":"Crecimiento mercado","value":"18%"},{"label":"Rating esperado","value":"4.6"}]',
 '[{"label":"Mercado","value":"LegalTech","icon":"globe"},{"label":"Modelo","value":"SaaS","icon":"layout"},{"label":"Retorno","value":"12-14 meses","icon":"trending-up"},{"label":"Tech","value":"Next.js + OpenAI","icon":"database"}]',
 '["Full source code","Templates library","Admin panel","Support"]',
 true, 4.9, false, 100),

('ecoshop','EcoShop','Marketplace de Productos Sostenibles',
 'Plataforma multi-vendedor enfocada exclusivamente en productos ecológicos y de comercio justo.',
 '$42,000 USD',
 'https://images.unsplash.com/photo-1542601906990-b4d3fb778b09?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"40k+"},{"label":"Crecimiento mercado","value":"25%"},{"label":"Rating esperado","value":"4.9"}]',
 '[{"label":"Mercado","value":"E-commerce","icon":"globe"},{"label":"Modelo","value":"Marketplace","icon":"layout"},{"label":"Retorno","value":"14-16 meses","icon":"trending-up"},{"label":"Tech","value":"React + Node.js","icon":"database"}]',
 '["Full source code","Vendor dashboard","Admin panel","Support"]',
 false, null, false, 110),

('travelwise','TravelWise','Planificador de Viajes con IA',
 'Crea itinerarios personalizados basados en preferencias del usuario y presupuesto.',
 '$15,000 USD',
 'https://images.unsplash.com/photo-1488646953014-85cb44e25828?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"100k+"},{"label":"Crecimiento mercado","value":"30%"},{"label":"Rating esperado","value":"4.8"}]',
 '[{"label":"Mercado","value":"Travel","icon":"globe"},{"label":"Modelo","value":"Ad-supported","icon":"layout"},{"label":"Retorno","value":"8-10 meses","icon":"trending-up"},{"label":"Tech","value":"Next.js + Python","icon":"database"}]',
 '["Full source code","API integrations","Admin panel","Support"]',
 false, null, false, 120),

('restostats','RestoStats','Analytics para Restaurantes',
 'Dashboard avanzado que analiza ventas, inventario y rendimiento del personal.',
 '$32,000 USD',
 'https://images.unsplash.com/photo-1552566626-52f8b828add9?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"15k+"},{"label":"Crecimiento mercado","value":"15%"},{"label":"Rating esperado","value":"4.7"}]',
 '[{"label":"Mercado","value":"Gastronomía","icon":"globe"},{"label":"Modelo","value":"SaaS B2B","icon":"layout"},{"label":"Retorno","value":"10-12 meses","icon":"trending-up"},{"label":"Tech","value":"React + D3.js","icon":"database"}]',
 '["Full source code","Data viz tools","Admin panel","Support"]',
 false, null, false, 130),

('cleancloud','CleanCloud','Gestión de Servicios de Limpieza',
 'Plataforma para empresas de limpieza que gestiona equipos, horarios y facturación.',
 '$22,000 USD',
 'https://images.unsplash.com/photo-1581578731548-c64695cc6958?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"7k+"},{"label":"Crecimiento mercado","value":"10%"},{"label":"Rating esperado","value":"4.5"}]',
 '[{"label":"Mercado","value":"Services","icon":"globe"},{"label":"Modelo","value":"SaaS B2B","icon":"layout"},{"label":"Retorno","value":"12-14 meses","icon":"trending-up"},{"label":"Tech","value":"Next.js + Node.js","icon":"database"}]',
 '["Full source code","Booking system","Admin panel","Support"]',
 false, null, false, 140),

('artivault','ArtiVault','Marketplace de Arte Digital NFT',
 'Galería exclusiva para artistas digitales con sistema de subastas y ventas directas.',
 '$65,000 USD',
 'https://images.unsplash.com/photo-1561070791-2526d30994b5?auto=format&fit=crop&q=80&w=800',
 '[{"label":"Usuarios potenciales","value":"12k+"},{"label":"Crecimiento mercado","value":"35%"},{"label":"Rating esperado","value":"4.9"}]',
 '[{"label":"Mercado","value":"Art","icon":"globe"},{"label":"Modelo","value":"Marketplace","icon":"layout"},{"label":"Retorno","value":"18-24 meses","icon":"trending-up"},{"label":"Tech","value":"React + Solidity","icon":"database"}]',
 '["Full source code","Smart contracts","Admin panel","Support"]',
 false, null, false, 150)

ON CONFLICT (id) DO UPDATE SET
    title           = EXCLUDED.title,
    category        = EXCLUDED.category,
    description     = EXCLUDED.description,
    price           = EXCLUDED.price,
    image           = EXCLUDED.image,
    stats           = EXCLUDED.stats,
    details         = EXCLUDED.details,
    included        = EXCLUDED.included,
    is_verified     = EXCLUDED.is_verified,
    security_rating = EXCLUDED.security_rating,
    seller_verified = EXCLUDED.seller_verified,
    sort_order      = EXCLUDED.sort_order;

-- =====================================================================
-- Seed: testimonios
-- =====================================================================
INSERT INTO public.testimonials (name, role, image, quote, sort_order) VALUES
('Sofia Martinez','CEO de FinTech Global',
 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?auto=format&fit=crop&q=80&w=200',
 'Adquirir un negocio con Nativos fue la mejor decisión de inversión. El proceso es transparente y el soporte excepcional.',10),
('Carlos Rodriguez','Inversionista Privado',
 'https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?auto=format&fit=crop&q=80&w=200',
 'La calidad de las oportunidades es incomparable. Compré una plataforma lista para escalar en 48 horas.',20),
('Ana Gómez','Emprendedora Serial',
 'https://images.unsplash.com/photo-1438761681033-6461ffad8d80?auto=format&fit=crop&q=80&w=200',
 'Nativos me permitió diversificar mi portafolio con negocios digitales rentables y sin complicaciones técnicas.',30);

-- =====================================================================
-- Seed: tech stack
-- =====================================================================
INSERT INTO public.tech_stack (name, sort_order) VALUES
('Next.js',10),('NestJS',20),('Supabase',30),('React Native',40),
('Flutter',50),('GraphQL',60),('TypeScript',70),('AWS',80),
('Stripe',90),('SendGrid',100),('Vercel',110),('PostgreSQL',120)
ON CONFLICT (name) DO UPDATE SET sort_order = EXCLUDED.sort_order;
