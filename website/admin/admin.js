// ============================================
// KOMAGIN LIMITED - ADMIN PANEL JAVASCRIPT
// Complete CMS Functionality
// ============================================

class KomaginAdmin {
    constructor() {
        this.apiBase = 'admin.php';
        
        this.currentItemId = null;
        this.currentType = null;
        
        // Data stores
        this.projects = [];
        this.services = [];
        this.testimonials = [];
        this.team = [];
        this.contacts = [];
        this.subscribers = [];
        this.settings = {};
        this.documents = [];
        this.documentsStats = {};
        this.documentsLoaded = false;
        this.documentsLoading = false;
        this.serviceDetailSections = [];
        this.projectScopeBlocks = [];
        this.projectGalleryImages = [];
        this.heroSlideImages = [];
        
        // Filtered data
        this.filteredProjects = [];
        this.activeCategoryFilter = 'all';
        this.activeStatusFilter = 'all';
        this.activeSearchTerm = '';
        this.filteredServices = [];
        this.filteredTestimonials = [];
        this.filteredTeam = [];
        this.filteredContacts = [];
        
        this.isLoading = false;
        
        this.initializeEventListeners();
        this.loadDashboardData();
        this.loadProjects();
        this.loadServices();
        this.loadTestimonials();
        this.loadTeam();
        this.loadContacts();
        this.loadSubscribers();
        this.loadSettings();
        this.loadProfileData();
        this.checkAuthStatus();
        
        this.startSessionChecker();
    }
    
    // ============================================
    // SESSION MANAGEMENT
    // ============================================
    
    startSessionChecker() {
        setInterval(() => {
            if (document.visibilityState === 'visible') {
                this.checkAuthStatus();
            }
        }, 300000);
    }
    
    async checkAuthStatus() {
        try {
            const response = await fetch(`${this.apiBase}?action=check_auth`);
            const result = await response.json();
            if (!result.success || !result.authenticated) {
                this.logout();
            }
        } catch (error) {
            console.error('Auth check failed:', error);
        }
    }
    
    async logout() {
        try {
            sessionStorage.removeItem('admin_logged_in');
            localStorage.removeItem('admin_remember');
            const response = await fetch(`${this.apiBase}?action=logout`);
            const result = await response.json();
            if (result.success) {
                window.location.href = 'auth.php?message=logged_out';
            } else {
                window.location.href = 'auth.php';
            }
        } catch (error) {
            window.location.href = 'auth.php';
        }
    }
    
    // ============================================
    // INITIALIZATION
    // ============================================
    
    initializeEventListeners() {
        // Navigation
        document.querySelectorAll('.nav-item').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const section = item.getAttribute('data-section');
                if (section) {
                    this.showSection(section);
                    this.loadSectionData(section);
                    this.closeMobileMenu();
                }
            });
        });
        
        // Project Form
        const projectForm = document.getElementById('projectForm');
        if (projectForm) projectForm.addEventListener('submit', (e) => { e.preventDefault(); this.saveProject(); });
        
        // Service Form
        const serviceForm = document.getElementById('serviceForm');
        if (serviceForm) serviceForm.addEventListener('submit', (e) => { e.preventDefault(); this.saveService(); });
        
        // Testimonial Form
        const testimonialForm = document.getElementById('testimonialForm');
        if (testimonialForm) testimonialForm.addEventListener('submit', (e) => { e.preventDefault(); this.saveTestimonial(); });
        
        // Team Form
        const teamForm = document.getElementById('teamForm');
        if (teamForm) teamForm.addEventListener('submit', (e) => { e.preventDefault(); this.saveTeam(); });
        
        // Settings Form
        const settingsForm = document.getElementById('settingsForm');
        if (settingsForm) settingsForm.addEventListener('submit', (e) => { e.preventDefault(); this.saveSettings(); });
        
        // Profile Form
        const profileForm = document.getElementById('profileForm');
        if (profileForm) profileForm.addEventListener('submit', (e) => { e.preventDefault(); this.updateProfile(); });
        
        // Password Form
        const passwordForm = document.getElementById('passwordForm');
        if (passwordForm) passwordForm.addEventListener('submit', (e) => { e.preventDefault(); this.changePassword(); });
        const newPasswordInput = document.getElementById('newPassword');
        if (newPasswordInput) newPasswordInput.addEventListener('input', () => this.updatePasswordStrength(newPasswordInput.value));
        
        // Newsletter Form
        const newsletterForm = document.getElementById('newsletterForm');
        if (newsletterForm) newsletterForm.addEventListener('submit', (e) => { e.preventDefault(); this.sendNewsletter(); });

        const siteContentForm = document.getElementById('siteContentForm');
        if (siteContentForm) siteContentForm.addEventListener('submit', (e) => { e.preventDefault(); this.saveSettings(); });
        
        // Quick Action Buttons
        const addProjectBtn = document.getElementById('addProjectBtn');
        if (addProjectBtn) addProjectBtn.addEventListener('click', () => this.showAddProjectForm());

        const manageProjectCategoriesBtn = document.getElementById('manageProjectCategoriesBtn');
        if (manageProjectCategoriesBtn) manageProjectCategoriesBtn.addEventListener('click', () => this.showSection('project-categories'));

        const addProjectCategoryBtn = document.getElementById('addProjectCategoryBtn');
        if (addProjectCategoryBtn) addProjectCategoryBtn.addEventListener('click', () => this.showAddProjectCategoryForm());

        const saveProjectCategoryBtn = document.getElementById('saveProjectCategoryBtn');
        if (saveProjectCategoryBtn) saveProjectCategoryBtn.addEventListener('click', () => this.saveProjectCategory());

        const cancelProjectCategoryBtn = document.getElementById('cancelProjectCategoryBtn');
        if (cancelProjectCategoryBtn) cancelProjectCategoryBtn.addEventListener('click', () => {
            document.getElementById('projectCategoryForm').style.display = 'none';
        });

        const backToProjectsBtn = document.getElementById('backToProjectsBtn');
        if (backToProjectsBtn) backToProjectsBtn.addEventListener('click', () => this.showSection('projects'));

        const quickAddProject = document.getElementById('quickAddProject');
        if (quickAddProject) quickAddProject.addEventListener('click', () => this.showAddProjectForm());
        
        const addServiceBtn = document.getElementById('addServiceBtn');
        if (addServiceBtn) addServiceBtn.addEventListener('click', () => this.showAddServiceForm());
        
        const quickAddService = document.getElementById('quickAddService');
        if (quickAddService) quickAddService.addEventListener('click', () => this.showAddServiceForm());
        
        const addTestimonialBtn = document.getElementById('addTestimonialBtn');
        if (addTestimonialBtn) addTestimonialBtn.addEventListener('click', () => this.showAddTestimonialForm());
        
        const quickAddTestimonial = document.getElementById('quickAddTestimonial');
        if (quickAddTestimonial) quickAddTestimonial.addEventListener('click', () => this.showAddTestimonialForm());
        
        const addTeamBtn = document.getElementById('addTeamBtn');
        if (addTeamBtn) addTeamBtn.addEventListener('click', () => this.showAddTeamForm());
        
        const quickViewSite = document.getElementById('quickViewSite');
        if (quickViewSite) quickViewSite.addEventListener('click', () => {
            window.open('../', '_blank');
        });
        
        // Back Buttons
        const backToProjects = document.getElementById('backToProjects');
        if (backToProjects) backToProjects.addEventListener('click', () => this.showSection('projects'));
        
        const backToServices = document.getElementById('backToServices');
        if (backToServices) backToServices.addEventListener('click', () => this.showSection('services'));
        
        const backToTestimonials = document.getElementById('backToTestimonials');
        if (backToTestimonials) backToTestimonials.addEventListener('click', () => this.showSection('testimonials'));
        
        const backToTeam = document.getElementById('backToTeam');
        if (backToTeam) backToTeam.addEventListener('click', () => this.showSection('team'));
        
        const cancelProjectBtn = document.getElementById('cancelProjectBtn');
        if (cancelProjectBtn) cancelProjectBtn.addEventListener('click', () => this.showSection('projects'));
        
        const cancelServiceBtn = document.getElementById('cancelServiceBtn');
        if (cancelServiceBtn) cancelServiceBtn.addEventListener('click', () => this.showSection('services'));
        
        const cancelTestimonialBtn = document.getElementById('cancelTestimonialBtn');
        if (cancelTestimonialBtn) cancelTestimonialBtn.addEventListener('click', () => this.showSection('testimonials'));
        
        const cancelTeamBtn = document.getElementById('cancelTeamBtn');
        if (cancelTeamBtn) cancelTeamBtn.addEventListener('click', () => this.showSection('team'));
        
        // Image Upload Buttons
        const uploadProjectImage = document.getElementById('uploadProjectImageBtn');
        if (uploadProjectImage) uploadProjectImage.addEventListener('click', () => document.getElementById('projectImage').click());

        const uploadProjectGallery = document.getElementById('uploadProjectGalleryBtn');
        if (uploadProjectGallery) uploadProjectGallery.addEventListener('click', () => document.getElementById('projectGalleryInput').click());

        const uploadTestimonialImage = document.getElementById('uploadTestimonialImageBtn');
        if (uploadTestimonialImage) uploadTestimonialImage.addEventListener('click', () => document.getElementById('testimonialImage').click());
        
        const uploadTeamImage = document.getElementById('uploadTeamImageBtn');
        if (uploadTeamImage) uploadTeamImage.addEventListener('click', () => document.getElementById('teamImage').click());

        const uploadHeroImage = document.getElementById('uploadHeroImageBtn');
        if (uploadHeroImage) uploadHeroImage.addEventListener('click', () => document.getElementById('heroImage').click());

        const uploadHeroSlideImage = document.getElementById('uploadHeroSlideImageBtn');
        if (uploadHeroSlideImage) uploadHeroSlideImage.addEventListener('click', () => document.getElementById('heroSlideImageInput').click());

        const uploadCtaImage = document.getElementById('uploadCtaImageBtn');
        if (uploadCtaImage) uploadCtaImage.addEventListener('click', () => document.getElementById('ctaImage').click());

        const addDocumentBtn = document.getElementById('addDocumentBtn');
        if (addDocumentBtn) addDocumentBtn.addEventListener('click', () => this.openDocumentForm());
        
        // Image Inputs
        const projectImage = document.getElementById('projectImage');
        if (projectImage) projectImage.addEventListener('change', (e) => this.handleImageUpload(e.target.files[0], 'project'));

        const projectGalleryInput = document.getElementById('projectGalleryInput');
        if (projectGalleryInput) projectGalleryInput.addEventListener('change', (e) => this.handleProjectGalleryUpload(e.target.files));

        const testimonialImage = document.getElementById('testimonialImage');
        if (testimonialImage) testimonialImage.addEventListener('change', (e) => this.handleImageUpload(e.target.files[0], 'testimonial'));
        
        const teamImage = document.getElementById('teamImage');
        if (teamImage) teamImage.addEventListener('change', (e) => this.handleImageUpload(e.target.files[0], 'team'));

        const heroImage = document.getElementById('heroImage');
        if (heroImage) heroImage.addEventListener('change', (e) => this.handleImageUpload(e.target.files[0], 'hero'));

        const heroSlideImageInput = document.getElementById('heroSlideImageInput');
        if (heroSlideImageInput) {
            heroSlideImageInput.addEventListener('change', (e) => this.uploadHeroSlideImages(e.target.files));
        }

        const ctaImage = document.getElementById('ctaImage');
        if (ctaImage) ctaImage.addEventListener('change', (e) => this.handleImageUpload(e.target.files[0], 'cta'));
        
        // Search and Filters
        const searchProjects = document.getElementById('searchProjects');
        if (searchProjects) searchProjects.addEventListener('input', (e) => this.filterProjects(e.target.value));
        
        const projectCategoryFilter = document.getElementById('projectCategoryFilter');
        if (projectCategoryFilter) projectCategoryFilter.addEventListener('change', (e) => this.filterProjectsByCategory(e.target.value));

        const projectStatusFilter = document.getElementById('projectStatusFilter');
        if (projectStatusFilter) projectStatusFilter.addEventListener('change', (e) => this.filterProjectsByStatus(e.target.value));

        const addProjectScopeBlockBtn = document.getElementById('addProjectScopeBlockBtn');
        if (addProjectScopeBlockBtn) addProjectScopeBlockBtn.addEventListener('click', () => this.addProjectScopeBlock());

        const addServiceDetailSectionBtn = document.getElementById('addServiceDetailSectionBtn');
        if (addServiceDetailSectionBtn) addServiceDetailSectionBtn.addEventListener('click', () => this.addServiceDetailSection());
        
        const searchServices = document.getElementById('searchServices');
        if (searchServices) searchServices.addEventListener('input', (e) => this.filterServices(e.target.value));
        
        const serviceCategoryFilter = document.getElementById('serviceCategoryFilter');
        if (serviceCategoryFilter) serviceCategoryFilter.addEventListener('change', (e) => this.filterServicesByCategory(e.target.value));
        
        const searchTestimonials = document.getElementById('searchTestimonials');
        if (searchTestimonials) searchTestimonials.addEventListener('input', (e) => this.filterTestimonials(e.target.value));
        
        const searchTeam = document.getElementById('searchTeam');
        if (searchTeam) searchTeam.addEventListener('input', (e) => this.filterTeam(e.target.value));
        
        const searchContacts = document.getElementById('searchContacts');
        if (searchContacts) searchContacts.addEventListener('input', (e) => this.filterContacts(e.target.value));
        
        const contactStatusFilter = document.getElementById('contactStatusFilter');
        if (contactStatusFilter) contactStatusFilter.addEventListener('change', (e) => this.filterContactsByStatus(e.target.value));
        
        // Modal Close Buttons
        const closeContactModal = document.getElementById('closeContactModal');
        if (closeContactModal) closeContactModal.addEventListener('click', () => this.hideModal('contactModal'));
        
        const closeContactModalBtn = document.getElementById('closeContactModalBtn');
        if (closeContactModalBtn) closeContactModalBtn.addEventListener('click', () => this.hideModal('contactModal'));
        
        const markReadBtn = document.getElementById('markReadBtn');
        if (markReadBtn) markReadBtn.addEventListener('click', () => this.updateContactStatus('read'));
        
        const closeConfirmModal = document.getElementById('closeConfirmModal');
        if (closeConfirmModal) closeConfirmModal.addEventListener('click', () => this.hideModal('confirmModal'));
        
        const cancelDeleteBtn = document.getElementById('cancelDeleteBtn');
        if (cancelDeleteBtn) cancelDeleteBtn.addEventListener('click', () => this.hideModal('confirmModal'));
        
        const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
        if (confirmDeleteBtn) confirmDeleteBtn.addEventListener('click', () => this.executeStyledConfirm());
        
        const refreshDashboard = document.getElementById('refreshDashboard');
        if (refreshDashboard) refreshDashboard.addEventListener('click', () => this.loadDashboardData());
        
        const dashboardLogout = document.getElementById('dashboardLogout');
        if (dashboardLogout) dashboardLogout.addEventListener('click', () => this.logout());
        
        const logoutLink = document.getElementById('logoutLink');
        if (logoutLink) logoutLink.addEventListener('click', (e) => { e.preventDefault(); this.logout(); });
        
        const refreshSession = document.getElementById('refreshSession');
        if (refreshSession) refreshSession.addEventListener('click', () => this.checkAuthStatus());
        
        const testNewsletterBtn = document.getElementById('testNewsletterBtn');
        if (testNewsletterBtn) testNewsletterBtn.addEventListener('click', () => this.sendNewsletter(true));

        const testEmailConfigBtn = document.getElementById('testEmailConfigBtn');
        if (testEmailConfigBtn) testEmailConfigBtn.addEventListener('click', async () => {
            try {
                await this.saveSettings(false);
                await this.testEmailConfiguration();
            } catch (error) {
                this.showError('Save the email settings before verification: ' + error.message);
            }
        });
        
        // Close modals on overlay click
        document.querySelectorAll('.modal').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) this.hideModal(modal.id);
            });
        });
        
        // Close on escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape') {
                document.querySelectorAll('.modal').forEach(modal => {
                    this.hideModal(modal.id);
                });
            }
        });
    }
    
    // ============================================
    // LOAD DATA METHODS
    // ============================================
    
    async loadDashboardData() {
        try {
            const response = await fetch(`${this.apiBase}?action=get_stats`);
            const result = await response.json();
            if (result.success) {
                const stats = result.data;
                document.getElementById('totalProjects').textContent = stats.total_projects || 0;
                document.getElementById('totalServices').textContent = stats.total_services || 0;
                document.getElementById('totalTestimonials').textContent = stats.total_testimonials || 0;
                document.getElementById('totalTeam').textContent = stats.total_team || 0;
                document.getElementById('totalContacts').textContent = stats.total_contacts || 0;
                document.getElementById('totalSubscribers').textContent = stats.total_subscribers || 0;
                document.getElementById('totalSubscribersStat').textContent = stats.total_subscribers || 0;
                
                const activeSubscribers = stats.total_subscribers || 0;
                document.getElementById('activeSubscribers').textContent = activeSubscribers;
                document.getElementById('newSubscribersMonth').textContent = Math.floor(activeSubscribers * 0.1) || 0;
            }
        } catch (error) {
            console.error('Error loading dashboard:', error);
        }
    }
    
    loadSectionData(section) {
        switch(section) {
            case 'projects': this.loadProjects(); break;
            case 'project-categories': this.loadProjectCategories(); break;
            case 'services': this.loadServices(); break;
            case 'testimonials': this.loadTestimonials(); break;
            case 'team': this.loadTeam(); break;
            case 'contacts': this.loadContacts(); break;
            case 'newsletter': this.loadSubscribers(); break;
            case 'documents': this.loadDocuments(true); break;
            case 'site-content': this.loadSettings(); break;
            case 'settings': this.loadSettings(); break;
            case 'jobs': this.loadJobs(); break;
            case 'applications': if (typeof this.loadApplications === 'function') this.loadApplications(); break;
        }
    }

    async loadJobs() {
        const container = document.getElementById('jobsTableBody');
        if (!container) return;
        container.innerHTML = '<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><span>Loading vacancies...</span></div>';
        try {
            const response = await fetch(`${this.apiBase}?action=jobs_list`, { credentials: 'same-origin' });
            const result = await response.json();
            if (!result.success) throw new Error(result.error || 'Failed to load job vacancies');
            const rows = result.data || [];
            this._jobsData = rows;
            if (!rows.length) {
                container.innerHTML = '<div class="empty-state"><i class="fas fa-briefcase"></i><span>No vacancies posted yet.</span></div>';
                return;
            }
            const badgeCls = (st) => st === 'published' ? 'job-badge-pub' : st === 'draft' ? 'job-badge-draft' : st === 'closed' ? 'job-badge-closed' : 'job-badge-arch';
            container.innerHTML = `<div class="job-card-list">${rows.map(job => {
                const apps = job.applications_count > 0 ? `<span class="job-badge job-badge-apps"><i class="fas fa-file-signature"></i> ${job.applications_count} app${job.applications_count !== 1 ? 's' : ''}</span>` : '';
                return `<div class="job-row-card">
                    <div class="job-row-icon"><i class="fas fa-briefcase"></i></div>
                    <div class="job-row-info">
                        <div class="job-row-top">
                            <span class="job-row-title">${this.escapeHtml(job.title || '-')}</span>
                            <span class="job-badge ${badgeCls(job.status || 'draft')}">${this.escapeHtml(this.label(job.status || 'draft'))}</span>
                            <span class="job-badge job-badge-type">${this.escapeHtml(this.label(job.type || 'full_time'))}</span>
                            ${apps}
                        </div>
                        <div class="job-row-meta">
                            ${job.department ? `<span><i class="fas fa-building"></i>${this.escapeHtml(job.department)}</span>` : ''}
                            ${job.location ? `<span><i class="fas fa-map-marker-alt"></i>${this.escapeHtml(job.location)}</span>` : ''}
                            <span><i class="fas fa-calendar"></i>${job.closing_date ? this.formatDate(job.closing_date) : 'Open until filled'}</span>
                        </div>
                    </div>
                    <div class="job-row-actions">
                        ${this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: 'Edit vacancy', attrs: `onclick="komaginAdmin.openJobForm('${job.id}')"` })}
                        ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete vacancy', attrs: `onclick="komaginAdmin.deleteJob('${job.id}')"` })}
                    </div>
                </div>`;
            }).join('')}</div>`;
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><span>${this.escapeHtml(error.message)}</span></div>`;
        }
    }

    // ============================================
    // PROJECT METHODS
    // ============================================
    
    async loadProjects() {
        this.showLoading(true);
        try {
            const [projRes, catRes] = await Promise.all([
                fetch(`${this.apiBase}?action=get_projects`).then(r => r.json()),
                fetch(`${this.apiBase}?action=get_project_categories`).then(r => r.json())
            ]);
            if (catRes.success && Array.isArray(catRes.data)) {
                this.projectCategories = catRes.data;
                this._populateProjectCategoryDropdowns();
            }
            if (projRes.success) {
                this.projects = projRes.data;
                this.applyProjectFilters(this.activeCategoryFilter, this.activeStatusFilter);
            }
        } catch (error) {
            console.error('Error loading projects:', error);
            this.showError('Failed to load projects');
        } finally {
            this.showLoading(false);
        }
    }

    _populateProjectCategoryDropdowns() {
        const cats = this.projectCategories || [];
        // Project form select
        const formSel = document.getElementById('projectCategory');
        if (formSel) {
            const current = formSel.value;
            formSel.innerHTML = cats.map(c =>
                `<option value="${this.escapeHtml(c.slug)}">${this.escapeHtml(c.name)}</option>`
            ).join('') || '<option value="">No categories — add one first</option>';
            if (current && cats.some(c => c.slug === current)) formSel.value = current;
        }
        // Projects list filter select
        const filterSel = document.getElementById('projectCategoryFilter');
        if (filterSel) {
            const current = filterSel.value || 'all';
            filterSel.innerHTML = '<option value="all">All Sub-Categories</option>' +
                cats.map(c => `<option value="${this.escapeHtml(c.slug)}">${this.escapeHtml(c.name)}</option>`).join('');
            filterSel.value = current;
        }
    }
    
    displayProjects() {
        const container = document.getElementById('projectsTableBody');
        if (!container) return;

        if (!this.filteredProjects || this.filteredProjects.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-folder-open"></i><p>No projects found</p></div>`;
            return;
        }

        container.innerHTML = `<div class="project-card-list">${this.filteredProjects.map(project => {
            const thumb = project.image_url
                ? `<img class="project-row-thumb" src="${this.escapeHtml(project.image_url)}" alt="${this.escapeHtml(project.name)}">`
                : `<div class="project-row-thumb-placeholder"><i class="fas fa-image"></i></div>`;
            const featuredBadge = Number(project.featured || 0) === 1
                ? `<span class="project-row-badge badge-featured"><i class="fas fa-star"></i> Featured</span>`
                : '';
            const branchBadge = project.branch_name
                ? `<span class="project-row-badge badge-branch"><i class="fas fa-code-branch"></i> ${this.escapeHtml(project.branch_name)}</span>`
                : '';
            const statusBadge = project.status === 'COMPLETED'
                ? `<span class="project-row-badge" style="background:rgba(45,125,70,0.12);color:#2D7D46;border:1px solid rgba(45,125,70,0.25)"><i class="fas fa-check-circle"></i> Completed</span>`
                : `<span class="project-row-badge" style="background:rgba(201,162,39,0.12);color:#A68318;border:1px solid rgba(201,162,39,0.25)"><i class="fas fa-clock"></i> In Progress</span>`;
            return `<div class="project-row-card">
                ${thumb}
                <div class="project-row-info">
                    <div class="project-row-title">${this.escapeHtml(project.name)}</div>
                    <div class="project-row-meta">
                        ${statusBadge}
                        <span class="project-row-badge badge-category">${this.escapeHtml(project.category || 'subdivision')}</span>
                        ${project.location ? `<span><i class="fas fa-map-marker-alt"></i> ${this.escapeHtml(project.location)}</span>` : ''}
                        ${branchBadge}
                        ${featuredBadge}
                    </div>
                </div>
                <div class="project-row-actions">
                    ${this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: 'Edit project', attrs: `onclick="komaginAdmin.editProject('${project.id}')"` })}
                    ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete project', attrs: `onclick="komaginAdmin.confirmDelete('${project.id}', 'project')"` })}
                </div>
            </div>`;
        }).join('')}</div>`;
    }
    
    filterProjects(searchTerm) {
        this.activeSearchTerm = searchTerm;
        this.applyProjectFilters(this.activeCategoryFilter, this.activeStatusFilter);
    }
    
    filterProjectsByCategory(category) {
        this.applyProjectFilters(category, this.activeStatusFilter);
    }

    filterProjectsByStatus(status) {
        this.activeStatusFilter = status;
        this.applyProjectFilters(this.activeCategoryFilter, status);
    }

    applyProjectFilters(category, status) {
        this.activeCategoryFilter = category;
        this.activeStatusFilter = status;
        const term = String(this.activeSearchTerm || '').toLowerCase().trim();
        this.filteredProjects = this.projects.filter(p => {
            const matchCategory = category === 'all' || p.category === category;
            const matchStatus = status === 'all' || p.status === status;
            const matchSearch = !term || 
                p.name.toLowerCase().includes(term) || 
                (p.location || '').toLowerCase().includes(term);
            return matchCategory && matchStatus && matchSearch;
        });
        this.displayProjects();
    }

    async populateProjectBranchSelect(selected = '', selectedLabel = '') {
        const select = document.getElementById('projectBranch');
        if (!select) return;
        const branches = await this.ensureBranchChoices();
        const selectedId = String(selected || '').trim();
        const hasSelectedChoice = selectedId !== '' && branches.some(branch => String(branch.id || '') === selectedId);
        const preservedChoice = selectedId !== '' && !hasSelectedChoice
            ? `<option value="${this.escapeHtml(selectedId)}" selected>${this.escapeHtml(selectedLabel || `Current branch (${selectedId})`)}</option>`
            : '';
        select.innerHTML = `<option value="">Not assigned to a branch</option>${preservedChoice}${branches.map(b => `<option value="${this.escapeHtml(b.id)}" ${selectedId === String(b.id || '') ? 'selected' : ''}>${this.escapeHtml(b.name || b.branch_code || b.id)}</option>`).join('')}`;
    }

    normalizeProjectGalleryPath(path) {
        const raw = String(path || '').trim();
        if (!raw) return '';
        if (/^(https?:)?\/\//.test(raw) || raw.startsWith('/')) return raw;
        if (raw.startsWith('admin/uploads/') || raw.startsWith('images/')) return raw;
        if (raw.startsWith('uploads/')) return `admin/${raw}`;
        return `admin/uploads/${raw.replace(/^\/+/, '')}`;
    }

    setProjectScopeBlocks(blocks = []) {
        const normalized = Array.isArray(blocks)
            ? blocks.map(block => ({
                title: String(block?.title || '').trim(),
                text: String(block?.text || block?.description || '').trim(),
                points: Array.isArray(block?.points)
                    ? block.points.map(point => String(point || '').trim()).filter(Boolean)
                    : String(block?.points || '')
                        .split(/\r?\n+/)
                        .map(point => point.trim())
                        .filter(Boolean)
            })).filter(block => block.title || block.text || block.points.length)
            : [];
        this.projectScopeBlocks = normalized.length ? normalized : [{ title: 'Project Scope', text: '', points: [] }];
        this.renderProjectScopeBlocks();
    }

    renderProjectScopeBlocks() {
        const container = document.getElementById('projectScopeBlocks');
        if (!container) return;
        const blocks = Array.isArray(this.projectScopeBlocks) && this.projectScopeBlocks.length
            ? this.projectScopeBlocks
            : [{ title: 'Project Scope', text: '', points: [] }];
        container.innerHTML = blocks.map((block, index) => `
            <div class="scope-block-card">
                <div class="scope-block-card-header">
                    <h4>Scope Block ${index + 1}</h4>
                    <button type="button" class="btn btn-secondary" data-project-scope-remove="${index}">Remove Block</button>
                </div>
                <div class="form-group">
                    <label>Block Title</label>
                    <input type="text" data-project-scope-field="title" data-project-scope-index="${index}" value="${this.escapeHtml(block.title || '')}" placeholder="e.g., Site Preparation & Earthworks">
                </div>
                <div class="form-group">
                    <label>Block Summary</label>
                    <textarea rows="4" data-project-scope-field="text" data-project-scope-index="${index}" placeholder="Short paragraph that introduces this scope area.">${this.escapeHtml(block.text || '')}</textarea>
                </div>
                <div class="form-group">
                    <label>Bullet Points</label>
                    <textarea rows="5" data-project-scope-field="points" data-project-scope-index="${index}" placeholder="One bullet point per line">${this.escapeHtml((block.points || []).join('\n'))}</textarea>
                </div>
            </div>
        `).join('');

        container.querySelectorAll('[data-project-scope-field]').forEach(input => {
            input.addEventListener('input', event => {
                const field = event.target.getAttribute('data-project-scope-field');
                const index = parseInt(event.target.getAttribute('data-project-scope-index') || '-1', 10);
                if (Number.isNaN(index) || !this.projectScopeBlocks[index]) return;
                if (field === 'points') {
                    this.projectScopeBlocks[index].points = event.target.value
                        .split(/\r?\n+/)
                        .map(point => point.trim())
                        .filter(Boolean);
                } else {
                    this.projectScopeBlocks[index][field] = event.target.value.trim();
                }
            });
        });

        container.querySelectorAll('[data-project-scope-remove]').forEach(button => {
            button.addEventListener('click', () => {
                const index = parseInt(button.getAttribute('data-project-scope-remove') || '-1', 10);
                if (Number.isNaN(index)) return;
                this.projectScopeBlocks.splice(index, 1);
                if (!this.projectScopeBlocks.length) {
                    this.projectScopeBlocks = [{ title: 'Project Scope', text: '', points: [] }];
                }
                this.renderProjectScopeBlocks();
            });
        });
    }

    addProjectScopeBlock(block = null) {
        if (!Array.isArray(this.projectScopeBlocks)) this.projectScopeBlocks = [];
        this.projectScopeBlocks.push({
            title: String(block?.title || '').trim() || `Scope Block ${this.projectScopeBlocks.length + 1}`,
            text: String(block?.text || '').trim(),
            points: Array.isArray(block?.points) ? block.points.filter(Boolean) : []
        });
        this.renderProjectScopeBlocks();
    }

    getProjectScopeBlocks() {
        if (!Array.isArray(this.projectScopeBlocks)) return [];
        return this.projectScopeBlocks
            .map(block => ({
                title: String(block?.title || '').trim() || 'Project Scope',
                text: String(block?.text || '').trim(),
                points: Array.isArray(block?.points)
                    ? block.points.map(point => String(point || '').trim()).filter(Boolean)
                    : []
            }))
            .filter(block => block.text || block.points.length || (block.title && block.title !== 'Project Scope'));
    }

    setServiceDetailSections(sections = []) {
        const normalized = Array.isArray(sections)
            ? sections.map(section => ({
                title: String(section?.title || '').trim(),
                text: String(section?.text || section?.description || '').trim(),
                points: Array.isArray(section?.points)
                    ? section.points.map(point => String(point || '').trim()).filter(Boolean)
                    : String(section?.points || '')
                        .split(/\r?\n+/)
                        .map(point => point.trim())
                        .filter(Boolean)
            })).filter(section => section.title || section.text || section.points.length)
            : [];
        this.serviceDetailSections = normalized.length ? normalized : [{ title: 'Service Overview', text: '', points: [] }];
        this.renderServiceDetailSections();
    }

    renderServiceDetailSections() {
        const container = document.getElementById('serviceDetailSections');
        if (!container) return;
        const sections = Array.isArray(this.serviceDetailSections) && this.serviceDetailSections.length
            ? this.serviceDetailSections
            : [{ title: 'Service Overview', text: '', points: [] }];
        container.innerHTML = sections.map((section, index) => `
            <div class="scope-block-card">
                <div class="scope-block-card-header">
                    <h4>Detail Section ${index + 1}</h4>
                    <button type="button" class="btn btn-secondary" data-service-detail-remove="${index}">Remove Section</button>
                </div>
                <div class="form-group">
                    <label>Sub Header</label>
                    <input type="text" data-service-detail-field="title" data-service-detail-index="${index}" value="${this.escapeHtml(section.title || '')}" placeholder="e.g., Scope of Works">
                </div>
                <div class="form-group">
                    <label>Paragraph</label>
                    <textarea rows="4" data-service-detail-field="text" data-service-detail-index="${index}" placeholder="Short paragraph that explains this service area.">${this.escapeHtml(section.text || '')}</textarea>
                </div>
                <div class="form-group">
                    <label>Bullet Points</label>
                    <textarea rows="5" data-service-detail-field="points" data-service-detail-index="${index}" placeholder="One bullet point per line">${this.escapeHtml((section.points || []).join('\n'))}</textarea>
                </div>
            </div>
        `).join('');

        container.querySelectorAll('[data-service-detail-field]').forEach(input => {
            input.addEventListener('input', event => {
                const field = event.target.getAttribute('data-service-detail-field');
                const index = parseInt(event.target.getAttribute('data-service-detail-index') || '-1', 10);
                if (Number.isNaN(index) || !this.serviceDetailSections[index]) return;
                if (field === 'points') {
                    this.serviceDetailSections[index].points = event.target.value
                        .split(/\r?\n+/)
                        .map(point => point.trim())
                        .filter(Boolean);
                } else {
                    this.serviceDetailSections[index][field] = event.target.value.trim();
                }
            });
        });

        container.querySelectorAll('[data-service-detail-remove]').forEach(button => {
            button.addEventListener('click', () => {
                const index = parseInt(button.getAttribute('data-service-detail-remove') || '-1', 10);
                if (Number.isNaN(index)) return;
                this.serviceDetailSections.splice(index, 1);
                if (!this.serviceDetailSections.length) {
                    this.serviceDetailSections = [{ title: 'Service Overview', text: '', points: [] }];
                }
                this.renderServiceDetailSections();
            });
        });
    }

    addServiceDetailSection(section = null) {
        if (!Array.isArray(this.serviceDetailSections)) this.serviceDetailSections = [];
        this.serviceDetailSections.push({
            title: String(section?.title || '').trim() || `Detail Section ${this.serviceDetailSections.length + 1}`,
            text: String(section?.text || '').trim(),
            points: Array.isArray(section?.points) ? section.points.filter(Boolean) : []
        });
        this.renderServiceDetailSections();
    }

    getServiceDetailSections() {
        if (!Array.isArray(this.serviceDetailSections)) return [];
        return this.serviceDetailSections
            .map(section => ({
                title: String(section?.title || '').trim() || 'Service Details',
                text: String(section?.text || '').trim(),
                points: Array.isArray(section?.points)
                    ? section.points.map(point => String(point || '').trim()).filter(Boolean)
                    : []
            }))
            .filter(section => section.text || section.points.length || (section.title && section.title !== 'Service Details'));
    }

    setProjectGalleryImages(images = []) {
        const normalized = (Array.isArray(images) ? images : [])
            .map(image => this.normalizeProjectGalleryPath(image))
            .filter(Boolean);
        this.projectGalleryImages = [...new Set(normalized)];
        this.renderProjectGalleryManager();
    }

    renderProjectGalleryManager() {
        const container = document.getElementById('projectGalleryGrid');
        if (!container) return;
        const images = Array.isArray(this.projectGalleryImages) ? this.projectGalleryImages : [];
        if (!images.length) {
            container.innerHTML = `<div class="project-gallery-empty">No gallery images added yet. Upload as many site, progress, or completion images as needed for this project.</div>`;
            return;
        }
        container.innerHTML = images.map((image, index) => `
            <div class="project-gallery-card">
                <img src="${this.escapeHtml(this.resolveAdminAssetUrl(image))}" alt="Project gallery image ${index + 1}">
                <div class="project-gallery-card-body">
                    <span>${this.escapeHtml(image)}</span>
                    <button type="button" class="btn btn-secondary" data-project-gallery-remove="${index}">Remove</button>
                </div>
            </div>
        `).join('');
        container.querySelectorAll('[data-project-gallery-remove]').forEach(button => {
            button.addEventListener('click', () => {
                const index = parseInt(button.getAttribute('data-project-gallery-remove') || '-1', 10);
                if (Number.isNaN(index)) return;
                this.projectGalleryImages.splice(index, 1);
                this.renderProjectGalleryManager();
            });
        });
    }

    async handleProjectGalleryUpload(files) {
        const galleryFiles = Array.from(files || []).filter(Boolean);
        if (!galleryFiles.length) return;
        this.showLoading(true);
        try {
            const uploaded = [];
            for (const file of galleryFiles) {
                const formData = new FormData();
                formData.append('image', file);
                const response = await fetch(`${this.apiBase}?action=upload_image`, {
                    method: 'POST',
                    body: formData
                });
                const result = await response.json();
                if (!result.success) {
                    throw new Error(result.error || `Upload failed for ${file.name}`);
                }
                uploaded.push(this.normalizeProjectGalleryPath(result.file_path || result.file_url || file.name));
            }
            this.setProjectGalleryImages([...(this.projectGalleryImages || []), ...uploaded]);
            const input = document.getElementById('projectGalleryInput');
            if (input) input.value = '';
            this.showSuccess(`${uploaded.length} project gallery image${uploaded.length === 1 ? '' : 's'} uploaded`);
        } catch (error) {
            console.error('Error uploading project gallery:', error);
            this.showError('Failed to upload project gallery: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    async showAddProjectForm() {
        this.currentItemId = null;
        this.currentType = 'project';
        document.getElementById('projectFormTitle').textContent = 'Add New Project';
        document.getElementById('projectForm').reset();
        document.getElementById('projectId').value = '';
        document.getElementById('projectImagePath').value = '';
        document.getElementById('projectFeatured').checked = false;
        const projectGalleryInput = document.getElementById('projectGalleryInput');
        if (projectGalleryInput) projectGalleryInput.value = '';
        const branchNameEl = document.getElementById('projectBranchName');
        if (branchNameEl) branchNameEl.value = '';
        if (!this.projectCategories || !this.projectCategories.length) {
            const catRes = await fetch(`${this.apiBase}?action=get_project_categories`).then(r => r.json()).catch(() => ({ success: false }));
            if (catRes.success) { this.projectCategories = catRes.data || []; this._populateProjectCategoryDropdowns(); }
        }
        this.resetImagePreview('project');
        this.setProjectScopeBlocks([]);
        this.setProjectGalleryImages([]);
        this.showSection('add-project');
    }
    
    async editProject(projectId) {
        const project = this.projects.find(p => p.id === projectId);
        if (!project) return;
        
        this.currentItemId = project.id;
        this.currentType = 'project';
        
        document.getElementById('projectFormTitle').textContent = 'Edit Project';
        document.getElementById('projectId').value = project.id;
        document.getElementById('projectName').value = project.name;
        document.getElementById('projectLocation').value = project.location || '';
        const branchNameEl = document.getElementById('projectBranchName');
        if (branchNameEl) branchNameEl.value = project.branch_name || '';
        document.getElementById('projectStatus').value = project.status || 'PENDING';
        if (!this.projectCategories || !this.projectCategories.length) {
            const catRes = await fetch(`${this.apiBase}?action=get_project_categories`).then(r => r.json()).catch(() => ({ success: false }));
            if (catRes.success) { this.projectCategories = catRes.data || []; this._populateProjectCategoryDropdowns(); }
        }
        document.getElementById('projectCategory').value = project.category || (this.projectCategories && this.projectCategories[0] ? this.projectCategories[0].slug : '');
        document.getElementById('projectDescription').value = project.description;
        document.getElementById('projectTechnologies').value = (project.technologies || []).join(', ');
        document.getElementById('projectFeatured').checked = Number(project.featured || 0) === 1;
        this.setProjectScopeBlocks(project.scope_sections || []);
        this.setProjectGalleryImages(project.gallery_images || []);
        
        if (project.image_url) {
            this.updateImagePreview('project', project.image_url, project.name);
            document.getElementById('projectImagePath').value = project.image;
        } else {
            this.resetImagePreview('project');
        }
        
        this.showSection('add-project');
    }
    
    async saveProject() {
        const technologies = document.getElementById('projectTechnologies').value
            .split(',')
            .map(t => t.trim())
            .filter(t => t);
        
        const data = {
            name: document.getElementById('projectName').value.trim(),
            location: document.getElementById('projectLocation').value.trim(),
            branch_name: document.getElementById('projectBranchName')?.value.trim() || '',
            category: document.getElementById('projectCategory').value,
            status: document.getElementById('projectStatus')?.value || 'PENDING',
            description: document.getElementById('projectDescription').value.trim(),
            technologies: technologies,
            image: document.getElementById('projectImagePath').value,
            gallery_images: Array.isArray(this.projectGalleryImages) ? this.projectGalleryImages : [],
            scope_sections: this.getProjectScopeBlocks(),
            featured: document.getElementById('projectFeatured').checked
        };
        
        if (!data.name) {
            this.showError('Project name is required');
            return;
        }
        if (!data.description) {
            this.showError('Project description is required');
            return;
        }
        
        this.showLoading(true);
        try {
            const url = this.currentItemId 
                ? `${this.apiBase}?action=update_project&id=${this.currentItemId}` 
                : `${this.apiBase}?action=create_project`;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (result.success) {
                this.showSuccess(this.currentItemId ? 'Project updated!' : 'Project created!');
                this.showSection('projects');
                this.loadProjects();
                this.loadDashboardData();
            } else {
                throw new Error(result.error || 'Save failed');
            }
        } catch (error) {
            console.error('Error saving project:', error);
            this.showError('Failed to save project: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    // ============================================
    // PROJECT CATEGORY METHODS
    // ============================================

    async loadProjectCategories() {
        this.showLoading(true);
        try {
            const res = await fetch(`${this.apiBase}?action=get_project_categories`).then(r => r.json());
            if (res.success) {
                this.projectCategories = res.data || [];
                this._renderProjectCategoriesList();
            } else {
                this.showError(res.error || 'Failed to load categories');
            }
        } catch (e) {
            console.error(e);
            this.showError('Failed to load project categories');
        } finally {
            this.showLoading(false);
        }
    }

    _renderProjectCategoriesList() {
        const container = document.getElementById('projectCategoriesContainer');
        if (!container) return;
        const cats = this.projectCategories || [];
        if (!cats.length) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-tags"></i><p>No categories yet. Click <strong>Add Category</strong> to create the first one.</p></div>`;
            return;
        }
        container.innerHTML = `<div class="project-card-list">${cats.map(c => `
            <div class="project-row-card">
                <div class="project-row-info">
                    <div class="project-row-title">${this.escapeHtml(c.name)}</div>
                    <div class="project-row-meta">
                        <span class="project-row-badge badge-category">${this.escapeHtml(c.slug)}</span>
                        ${c.description ? `<span style="color:var(--text-secondary);font-size:13px">${this.escapeHtml(c.description)}</span>` : ''}
                    </div>
                </div>
                <div class="project-row-actions">
                    ${this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: 'Edit', attrs: `onclick="komaginAdmin.editProjectCategory('${c.id}')"` })}
                    ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete', attrs: `onclick="komaginAdmin.deleteProjectCategory('${c.id}')"` })}
                </div>
            </div>`).join('')}</div>`;
    }

    showAddProjectCategoryForm() {
        document.getElementById('projectCategoryFormTitle').textContent = 'Add Category';
        document.getElementById('projectCategoryId').value = '';
        document.getElementById('projectCategoryName').value = '';
        document.getElementById('projectCategorySlug').value = '';
        document.getElementById('projectCategoryDescription').value = '';
        document.getElementById('projectCategorySortOrder').value = '0';
        document.getElementById('projectCategoryForm').style.display = '';
        document.getElementById('projectCategoryName').focus();
    }

    editProjectCategory(id) {
        const cat = (this.projectCategories || []).find(c => c.id === id);
        if (!cat) return;
        document.getElementById('projectCategoryFormTitle').textContent = 'Edit Category';
        document.getElementById('projectCategoryId').value = cat.id;
        document.getElementById('projectCategoryName').value = cat.name;
        document.getElementById('projectCategorySlug').value = cat.slug;
        document.getElementById('projectCategoryDescription').value = cat.description || '';
        document.getElementById('projectCategorySortOrder').value = cat.sort_order || 0;
        document.getElementById('projectCategoryForm').style.display = '';
        document.getElementById('projectCategoryName').focus();
    }

    async saveProjectCategory() {
        const id   = document.getElementById('projectCategoryId').value.trim();
        const name = document.getElementById('projectCategoryName').value.trim();
        const slug = document.getElementById('projectCategorySlug').value.trim();
        const desc = document.getElementById('projectCategoryDescription').value.trim();
        const ord  = parseInt(document.getElementById('projectCategorySortOrder').value) || 0;
        if (!name) { this.showError('Category name is required'); return; }
        this.showLoading(true);
        try {
            const res = await fetch(`${this.apiBase}?action=save_project_category`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ id, name, slug, description: desc, sort_order: ord })
            }).then(r => r.json());
            if (res.success) {
                this.showSuccess(id ? 'Category updated' : 'Category created');
                document.getElementById('projectCategoryForm').style.display = 'none';
                await this.loadProjectCategories();
            } else {
                this.showError(res.error || 'Save failed');
            }
        } catch (e) {
            this.showError('Failed to save category');
        } finally {
            this.showLoading(false);
        }
    }

    async deleteProjectCategory(id) {
        if (!confirm('Delete this category? Projects using it must be reassigned first.')) return;
        this.showLoading(true);
        try {
            const res = await fetch(`${this.apiBase}?action=delete_project_category&id=${encodeURIComponent(id)}`).then(r => r.json());
            if (res.success) {
                this.showSuccess('Category deleted');
                await this.loadProjectCategories();
            } else {
                this.showError(res.error || 'Delete failed');
            }
        } catch (e) {
            this.showError('Failed to delete category');
        } finally {
            this.showLoading(false);
        }
    }

    // ============================================
    // SERVICE METHODS
    // ============================================

    async loadServices() {
        this.showLoading(true);
        try {
            const response = await fetch(`${this.apiBase}?action=get_services`);
            const result = await response.json();
            if (result.success) {
                this.services = result.data;
                this.filteredServices = [...this.services];
                this.displayServices();
            } else {
                throw new Error(result.error || 'Failed to load services');
            }
        } catch (error) {
            console.error('Error loading services:', error);
            this.showError('Failed to load services');
        } finally {
            this.showLoading(false);
        }
    }
    
    displayServices() {
        const container = document.getElementById('servicesList');
        if (!container) return;

        if (!this.filteredServices || this.filteredServices.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-cogs"></i><p>No services found</p></div>`;
            return;
        }

        container.innerHTML = `<div class="svc-card-list">${this.filteredServices.map(service => {
            const featured = Number(service.featured || 0) === 1;
            const sections = Array.isArray(service.detail_sections) ? service.detail_sections.length : 0;
            const categoryLabel = this.serviceCategoryLabel(service.category || 'core');
            return `<div class="svc-row-card">
                <div class="svc-row-icon"><i class="fas ${this.escapeHtml(service.icon || 'fa-cog')}"></i></div>
                <div class="svc-row-info">
                    <div class="svc-row-title">${this.escapeHtml(service.name)}</div>
                    <div class="svc-row-desc">${this.escapeHtml((service.description || '').slice(0,120))}${(service.description || '').length > 120 ? '...' : ''}</div>
                    <div class="svc-row-meta">
                        <span class="svc-badge svc-badge-cat"><i class="fas fa-tag"></i> ${this.escapeHtml(categoryLabel)}</span>
                        ${featured ? `<span class="svc-badge svc-badge-info"><i class="fas fa-star"></i> Featured</span>` : ''}
                        <span class="svc-badge svc-badge-order"><i class="fas fa-list-ol"></i> Order ${this.escapeHtml(String(service.order || 0))}</span>
                        ${sections > 0 ? `<span class="svc-badge svc-badge-info"><i class="fas fa-layer-group"></i> ${sections} section${sections !== 1 ? 's' : ''}</span>` : ''}
                    </div>
                </div>
                <div class="svc-row-actions">
                    ${this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: 'Edit service', attrs: `onclick="komaginAdmin.editService('${service.id}')"` })}
                    ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete service', attrs: `onclick="komaginAdmin.confirmDelete('${service.id}', 'service')"` })}
                </div>
            </div>`;
        }).join('')}</div>`;
    }
    
    filterServices(searchTerm) {
        if (!searchTerm) {
            this.filteredServices = [...this.services];
        } else {
            const term = searchTerm.toLowerCase();
            this.filteredServices = this.services.filter(s => s.name.toLowerCase().includes(term));
        }
        this.displayServices();
    }

    serviceCategoryLabel(category = 'core') {
        const labels = {
            core: 'Core Services',
            structural: 'Structural & Building',
            infrastructure: 'Infrastructure',
            trades: 'Trades & Specialized',
            management: 'Management & Consulting'
        };
        return labels[String(category || 'core')] || this.label(category || 'core');
    }
    
    filterServicesByCategory(category) {
        if (category === 'all') {
            this.filteredServices = [...this.services];
        } else {
            this.filteredServices = this.services.filter(s => s.category === category);
        }
        this.displayServices();
    }
    
    showAddServiceForm() {
        this.currentItemId = null;
        this.currentType = 'service';
        document.getElementById('serviceFormTitle').textContent = 'Add New Service';
        document.getElementById('serviceForm').reset();
        document.getElementById('serviceId').value = '';
        document.getElementById('serviceIcon').value = 'fa-cog';
        document.getElementById('serviceFeatured').checked = false;
        document.getElementById('serviceDetailIntro').value = '';
        document.querySelectorAll('#serviceIconPicker .icon-option').forEach(opt => {
            opt.classList.remove('active');
            if (opt.dataset.icon === 'fa-cog') opt.classList.add('active');
        });
        this.setServiceDetailSections([]);
        this.showSection('add-service');
    }
    
    async editService(serviceId) {
        const service = this.services.find(s => s.id === serviceId);
        if (!service) return;
        
        this.currentItemId = service.id;
        this.currentType = 'service';
        
        document.getElementById('serviceFormTitle').textContent = 'Edit Service';
        document.getElementById('serviceId').value = service.id;
        document.getElementById('serviceName').value = service.name;
        document.getElementById('serviceCategory').value = service.category || 'core';
        document.getElementById('serviceDescription').value = service.description;
        document.getElementById('serviceDetailIntro').value = service.detail_intro || '';
        document.getElementById('serviceIcon').value = service.icon || 'fa-cog';
        document.getElementById('serviceFeatured').checked = Number(service.featured || 0) === 1;
        
        document.querySelectorAll('#serviceIconPicker .icon-option').forEach(opt => {
            opt.classList.remove('active');
            if (opt.dataset.icon === service.icon) opt.classList.add('active');
        });

        this.setServiceDetailSections(service.detail_sections || []);
        
        this.showSection('add-service');
    }
    
    async saveService() {
        const data = {
            name: document.getElementById('serviceName').value.trim(),
            category: document.getElementById('serviceCategory').value,
            description: document.getElementById('serviceDescription').value.trim(),
            detail_intro: document.getElementById('serviceDetailIntro').value.trim(),
            detail_sections: this.getServiceDetailSections(),
            icon: document.getElementById('serviceIcon').value,
            featured: document.getElementById('serviceFeatured').checked
        };
        
        if (!data.name) {
            this.showError('Service name is required');
            return;
        }
        if (!data.description) {
            this.showError('Service description is required');
            return;
        }
        
        this.showLoading(true);
        try {
            const url = this.currentItemId 
                ? `${this.apiBase}?action=update_service&id=${this.currentItemId}` 
                : `${this.apiBase}?action=create_service`;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (result.success) {
                this.showSuccess(this.currentItemId ? 'Service updated!' : 'Service created!');
                this.showSection('services');
                this.loadServices();
                this.loadDashboardData();
            } else {
                throw new Error(result.error || 'Save failed');
            }
        } catch (error) {
            console.error('Error saving service:', error);
            this.showError('Failed to save service: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    // ============================================
    // TESTIMONIAL METHODS
    // ============================================
    
    async loadTestimonials() {
        this.showLoading(true);
        try {
            const response = await fetch(`${this.apiBase}?action=get_testimonials`);
            const result = await response.json();
            if (result.success) {
                this.testimonials = result.data;
                this.filteredTestimonials = [...this.testimonials];
                this.displayTestimonials();
            }
        } catch (error) {
            console.error('Error loading testimonials:', error);
        } finally {
            this.showLoading(false);
        }
    }
    
    displayTestimonials() {
        const container = document.getElementById('testimonialsList');
        if (!container) return;

        if (!this.filteredTestimonials || this.filteredTestimonials.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-star"></i><p>No testimonials found</p></div>`;
            return;
        }

        container.innerHTML = `<div class="tmt-card-list">${this.filteredTestimonials.map(testimonial => {
            let stars = '';
            for (let i = 0; i < 5; i++) {
                stars += i < (testimonial.rating || 5) ? '<i class="fas fa-star"></i>' : '<i class="far fa-star"></i>';
            }
            const initial = testimonial.name ? testimonial.name.charAt(0).toUpperCase() : '?';
            const avatar = testimonial.image_url
                ? `<img src="${this.escapeHtml(testimonial.image_url)}" alt="${this.escapeHtml(testimonial.name || 'Client')}">`
                : initial;
            return `<div class="tmt-row-card">
                <div class="tmt-row-avatar">${avatar}</div>
                <div class="tmt-row-info">
                    <div class="tmt-row-top">
                        <span class="tmt-row-name">${this.escapeHtml(testimonial.name)}</span>
                        <span class="tmt-row-stars">${stars}</span>
                    </div>
                    <div class="tmt-row-role">${this.escapeHtml(testimonial.role || 'Client')}</div>
                    <div class="tmt-row-quote">"${this.escapeHtml(testimonial.content || '')}"</div>
                </div>
                <div class="tmt-row-actions">
                    ${this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: 'Edit testimonial', attrs: `onclick="komaginAdmin.editTestimonial('${testimonial.id}')"` })}
                    ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete testimonial', attrs: `onclick="komaginAdmin.confirmDelete('${testimonial.id}', 'testimonial')"` })}
                </div>
            </div>`;
        }).join('')}</div>`;
    }
    
    filterTestimonials(searchTerm) {
        if (!searchTerm) {
            this.filteredTestimonials = [...this.testimonials];
        } else {
            const term = searchTerm.toLowerCase();
            this.filteredTestimonials = this.testimonials.filter(t => 
                t.name.toLowerCase().includes(term) || 
                t.content.toLowerCase().includes(term)
            );
        }
        this.displayTestimonials();
    }
    
    showAddTestimonialForm() {
        this.currentItemId = null;
        this.currentType = 'testimonial';
        document.getElementById('testimonialFormTitle').textContent = 'Add New Testimonial';
        document.getElementById('testimonialForm').reset();
        document.getElementById('testimonialId').value = '';
        document.getElementById('testimonialImagePath').value = '';
        document.getElementById('testimonialRating').value = '5';
        this.resetImagePreview('testimonial');
        this.showSection('add-testimonial');
    }
    
    async editTestimonial(testimonialId) {
        const testimonial = this.testimonials.find(t => t.id === testimonialId);
        if (!testimonial) return;
        
        this.currentItemId = testimonial.id;
        this.currentType = 'testimonial';
        
        document.getElementById('testimonialFormTitle').textContent = 'Edit Testimonial';
        document.getElementById('testimonialId').value = testimonial.id;
        document.getElementById('testimonialName').value = testimonial.name;
        document.getElementById('testimonialRole').value = testimonial.role || '';
        document.getElementById('testimonialRating').value = testimonial.rating || 5;
        document.getElementById('testimonialContent').value = testimonial.content;
        
        if (testimonial.image_url) {
            this.updateImagePreview('testimonial', testimonial.image_url, testimonial.name);
            document.getElementById('testimonialImagePath').value = testimonial.image;
        } else {
            this.resetImagePreview('testimonial');
        }
        
        this.showSection('add-testimonial');
    }
    
    async saveTestimonial() {
        const data = {
            name: document.getElementById('testimonialName').value.trim(),
            role: document.getElementById('testimonialRole').value.trim(),
            rating: parseInt(document.getElementById('testimonialRating').value),
            content: document.getElementById('testimonialContent').value.trim(),
            image: document.getElementById('testimonialImagePath').value
        };
        
        if (!data.name) {
            this.showError('Name is required');
            return;
        }
        if (!data.content) {
            this.showError('Testimonial content is required');
            return;
        }
        
        this.showLoading(true);
        try {
            const url = this.currentItemId 
                ? `${this.apiBase}?action=update_testimonial&id=${this.currentItemId}` 
                : `${this.apiBase}?action=create_testimonial`;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (result.success) {
                this.showSuccess(this.currentItemId ? 'Testimonial updated!' : 'Testimonial created!');
                this.showSection('testimonials');
                this.loadTestimonials();
                this.loadDashboardData();
            } else {
                throw new Error(result.error || 'Save failed');
            }
        } catch (error) {
            console.error('Error saving testimonial:', error);
            this.showError('Failed to save testimonial: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    // ============================================
    // TEAM METHODS
    // ============================================
    
    async loadTeam() {
        this.showLoading(true);
        try {
            const response = await fetch(`${this.apiBase}?action=get_team`);
            const result = await response.json();
            if (result.success) {
                this.team = result.data;
                this.filteredTeam = [...this.team];
                this.displayTeam();
            }
        } catch (error) {
            console.error('Error loading team:', error);
        } finally {
            this.showLoading(false);
        }
    }
    
    displayTeam() {
        const container = document.getElementById('teamList');
        if (!container) return;

        if (!this.filteredTeam || this.filteredTeam.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-users"></i><p>No team members found</p></div>`;
            return;
        }

        container.innerHTML = `<div class="team-card-list">${this.filteredTeam.map(member => {
            const photoEl = member.image_url
                ? `<img class="team-row-photo" src="${this.escapeHtml(member.image_url)}" alt="${this.escapeHtml(member.name)}">`
                : `<div class="team-row-photo team-row-initial"><i class="fas fa-user"></i></div>`;
            const emailEl = member.email
                ? `<div class="team-row-email"><i class="fas fa-envelope"></i>${this.escapeHtml(member.email)}</div>`
                : '';
            return `<div class="team-row-card">
                ${photoEl}
                <div class="team-row-info">
                    <div class="team-row-name">${this.escapeHtml(member.name)}</div>
                    <div class="team-row-position">${this.escapeHtml(member.position)}</div>
                    ${member.bio ? `<div class="team-row-bio">${this.escapeHtml(member.bio)}</div>` : ''}
                    ${emailEl}
                </div>
                <div class="team-row-actions">
                    ${this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: 'Edit team member', attrs: `onclick="komaginAdmin.editTeam('${member.id}')"` })}
                    ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete team member', attrs: `onclick="komaginAdmin.confirmDelete('${member.id}', 'team')"` })}
                </div>
            </div>`;
        }).join('')}</div>`;
    }
    
    filterTeam(searchTerm) {
        if (!searchTerm) {
            this.filteredTeam = [...this.team];
        } else {
            const term = searchTerm.toLowerCase();
            this.filteredTeam = this.team.filter(m => 
                m.name.toLowerCase().includes(term) || 
                m.position.toLowerCase().includes(term)
            );
        }
        this.displayTeam();
    }
    
    showAddTeamForm() {
        this.currentItemId = null;
        this.currentType = 'team';
        document.getElementById('teamFormTitle').textContent = 'Add Team Member';
        document.getElementById('teamForm').reset();
        document.getElementById('teamId').value = '';
        document.getElementById('teamImagePath').value = '';
        this.resetImagePreview('team');
        this.showSection('add-team');
    }
    
    async editTeam(teamId) {
        const member = this.team.find(m => m.id === teamId);
        if (!member) return;
        
        this.currentItemId = member.id;
        this.currentType = 'team';
        
        document.getElementById('teamFormTitle').textContent = 'Edit Team Member';
        document.getElementById('teamId').value = member.id;
        document.getElementById('teamName').value = member.name;
        document.getElementById('teamPosition').value = member.position;
        document.getElementById('teamBio').value = member.bio || '';
        document.getElementById('teamEmail').value = member.email || '';
        
        if (member.image_url) {
            this.updateImagePreview('team', member.image_url, member.name);
            document.getElementById('teamImagePath').value = member.image;
        } else {
            this.resetImagePreview('team');
        }
        
        this.showSection('add-team');
    }
    
    async saveTeam() {
        const data = {
            name: document.getElementById('teamName').value.trim(),
            position: document.getElementById('teamPosition').value.trim(),
            bio: document.getElementById('teamBio').value.trim(),
            email: document.getElementById('teamEmail').value.trim(),
            image: document.getElementById('teamImagePath').value
        };
        
        if (!data.name) {
            this.showError('Name is required');
            return;
        }
        if (!data.position) {
            this.showError('Position is required');
            return;
        }
        
        this.showLoading(true);
        try {
            const url = this.currentItemId 
                ? `${this.apiBase}?action=update_team&id=${this.currentItemId}` 
                : `${this.apiBase}?action=create_team`;
            
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            
            const result = await response.json();
            if (result.success) {
                this.showSuccess(this.currentItemId ? 'Team member updated!' : 'Team member added!');
                this.showSection('team');
                this.loadTeam();
                this.loadDashboardData();
            } else {
                throw new Error(result.error || 'Save failed');
            }
        } catch (error) {
            console.error('Error saving team member:', error);
            this.showError('Failed to save team member: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    // ============================================
    // CONTACT METHODS
    // ============================================
    
    async loadContacts() {
        const container = document.getElementById('contactsTableBody');
        try {
            const response = await fetch(`${this.apiBase}?action=get_contacts`);
            const result = await response.json();
            if (result.success) {
                this.contacts = result.data;
                this.applyContactFilters();
                this.updateRecentContacts();
            } else if (container) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>${this.escapeHtml(result.error || 'Unable to load contact messages')}</p></div>`;
            }
        } catch (error) {
            console.error('Error loading contacts:', error);
            if (container) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-wifi"></i><p>Failed to load contact messages</p></div>`;
            }
        }
    }
    
    displayContacts() {
        const container = document.getElementById('contactsTableBody');
        if (!container) return;

        if (!this.filteredContacts || this.filteredContacts.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-envelope"></i><p>No messages found</p></div>`;
            return;
        }

        container.innerHTML = `<div class="msg-card-list">${this.filteredContacts.map(contact => {
            const isUnread = !contact.status || contact.status === 'new';
            const statusMap = { new: 'msg-badge-new', read: 'msg-badge-read', replied: 'msg-badge-replied' };
            const normalizedStatus = contact.status || 'new';
            const badgeClass = statusMap[normalizedStatus] || 'msg-badge-new';
            const initial = contact.name ? contact.name.charAt(0).toUpperCase() : '?';
            const preview = this.escapeHtml((contact.message || '').slice(0, 100));
            return `<div class="msg-row-card${isUnread ? ' msg-row-unread' : ''}">
                <div class="msg-row-avatar">${initial}</div>
                <div class="msg-row-info">
                    <div class="msg-row-top">
                        <span class="msg-row-name">${this.escapeHtml(contact.name)}</span>
                        <span class="msg-badge ${badgeClass}">${this.escapeHtml(this.label(normalizedStatus))}</span>
                    </div>
                    <div class="msg-row-subject">${this.escapeHtml(contact.subject || 'General Enquiry')}</div>
                    <div class="msg-row-meta">
                        <span><i class="fas fa-envelope"></i>${this.escapeHtml(contact.email)}</span>
                        <span><i class="fas fa-calendar"></i>${this.formatDate(contact.created_at)}</span>
                        ${contact.phone ? `<span><i class="fas fa-phone"></i>${this.escapeHtml(contact.phone)}</span>` : ''}
                    </div>
                    ${preview ? `<div class="msg-row-preview">${preview}</div>` : ''}
                </div>
                <div class="msg-row-actions">
                    ${this.iconButton({ variant: 'btn-outline', icon: 'fa-eye', label: 'View message', attrs: `onclick="window.komaginAdmin.viewContact('${contact.id}')"` })}
                    ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete message', attrs: `onclick="window.komaginAdmin.confirmDelete('${contact.id}', 'contact')"` })}
                </div>
            </div>`;
        }).join('')}</div>`;
    }
    
    filterContacts(searchTerm) {
        this.applyContactFilters(searchTerm);
    }
    
    filterContactsByStatus(status) {
        this.applyContactFilters(undefined, status);
    }

    applyContactFilters(searchTerm, statusFilter) {
        const searchInput = document.getElementById('searchContacts');
        const statusSelect = document.getElementById('contactStatusFilter');
        const term = typeof searchTerm === 'string' ? searchTerm.trim().toLowerCase() : (searchInput?.value || '').trim().toLowerCase();
        const activeStatus = typeof statusFilter === 'string' ? statusFilter : (statusSelect?.value || 'all');

        this.filteredContacts = (this.contacts || []).filter(contact => {
            const matchesStatus = activeStatus === 'all' || (contact.status || 'new') === activeStatus;
            if (!matchesStatus) {
                return false;
            }

            if (!term) {
                return true;
            }

            return [
                contact.name,
                contact.email,
                contact.phone,
                contact.subject,
                contact.message,
                contact.status
            ].some(value => String(value || '').toLowerCase().includes(term));
        });

        this.displayContacts();
    }
    
    updateRecentContacts() {
        const container = document.getElementById('recentContacts');
        if (!container) return;
        
        const recent = this.contacts.slice(0, 5);
        if (recent.length === 0) {
            container.innerHTML = '<div class="empty-state"><p>No messages yet</p></div>';
            return;
        }
        
        container.innerHTML = recent.map(contact => `
            <div class="activity-item" onclick="window.komaginAdmin.viewContact('${contact.id}')">
                <div class="activity-title">${this.escapeHtml(contact.name)} - ${this.escapeHtml(contact.subject || 'Message')}</div>
                <div class="activity-date">${this.formatDate(contact.created_at)} - ${this.label(contact.status || 'new')}</div>
            </div>
        `).join('');
    }
    
    async viewContact(contactId) {
        const contact = this.contacts.find(c => c.id === contactId);
        if (!contact) return;
        
        this.currentItemId = contactId;
        
        const modalBody = document.getElementById('contactModalBody');
        if (modalBody) {
            modalBody.innerHTML = `
                <div class="contact-details">
                    <p><strong>Name:</strong> ${this.escapeHtml(contact.name)}</p>
                    <p><strong>Email:</strong> ${this.escapeHtml(contact.email)}</p>
                    ${contact.phone ? `<p><strong>Phone:</strong> ${this.escapeHtml(contact.phone)}</p>` : ''}
                    <p><strong>Subject:</strong> ${this.escapeHtml(contact.subject || 'General')}</p>
                    <p><strong>Date:</strong> ${this.formatDateTime(contact.created_at)}</p>
                    <p><strong>Status:</strong> <span id="contactStatusValue">${this.escapeHtml(this.label(contact.status || 'new'))}</span></p>
                    <hr>
                    <p><strong>Message:</strong></p>
                    <p>${this.escapeHtml(contact.message).replace(/\n/g, '<br>')}</p>
                </div>
            `;
        }
        
        this.syncContactModalActions(contact.status || 'new');
        this.showModal('contactModal');
        
        if (contact.status === 'new') {
            this.updateContactStatus('read');
        }
    }
    
    syncContactModalActions(status) {
        const markReadBtn = document.getElementById('markReadBtn');
        if (!markReadBtn) return;

        const normalizedStatus = status || 'new';
        const isNew = normalizedStatus === 'new';
        markReadBtn.disabled = !isNew;
        markReadBtn.classList.toggle('is-disabled', !isNew);
        markReadBtn.textContent = isNew ? 'Mark as Read' : 'Already Read';
    }

    async updateContactStatus(status) {
        if (!this.currentItemId) return;
        
        try {
            const response = await fetch(`${this.apiBase}?action=update_contact_status&id=${this.currentItemId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ status })
            });
            const result = await response.json();
            if (result.success) {
                const activeContact = this.contacts.find(contact => contact.id === this.currentItemId);
                if (activeContact) {
                    activeContact.status = status;
                }
                const statusValue = document.getElementById('contactStatusValue');
                if (statusValue) {
                    statusValue.textContent = this.label(status);
                }
                this.syncContactModalActions(status);
                this.applyContactFilters();
                this.updateRecentContacts();
                this.loadContacts();
            }
        } catch (error) {
            console.error('Error updating contact:', error);
        }
    }
    
    // ============================================
    // NEWSLETTER METHODS
    // ============================================
    
    async loadSubscribers() {
        const container = document.getElementById('subscribersTableBody');
        try {
            const response = await fetch(`${this.apiBase}?action=get_subscribers`);
            const result = await response.json();
            if (result.success) {
                this.subscribers = result.data;
                this.displaySubscribers();
                this.updateNewsletterSubscriberStats();
            } else if (container) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>${this.escapeHtml(result.error || 'Unable to load subscribers')}</p></div>`;
            }
        } catch (error) {
            console.error('Error loading subscribers:', error);
            if (container) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-wifi"></i><p>Failed to load subscribers</p></div>`;
            }
        }
    }
    
    displaySubscribers() {
        const container = document.getElementById('subscribersTableBody');
        if (!container) return;

        if (!this.subscribers || this.subscribers.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-users"></i><p>No subscribers yet</p></div>`;
            return;
        }

        container.innerHTML = `<div class="sub-card-list">${this.subscribers.map(sub => {
            const isActive = !sub.status || sub.status === 'active';
            const badgeClass = isActive ? 'sub-badge-active' : 'sub-badge-inactive';
            return `<div class="sub-row-card">
                <div class="sub-row-icon"><i class="fas fa-envelope"></i></div>
                <div class="sub-row-info">
                    <div class="sub-row-email">${this.escapeHtml(sub.email)}</div>
                    <div class="sub-row-date"><i class="fas fa-calendar"></i>${this.formatDate(sub.subscribed_at)}</div>
                </div>
                <span class="sub-badge ${badgeClass}"><i class="fas fa-circle"></i> ${this.escapeHtml(this.label(sub.status || 'active'))}</span>
            </div>`;
        }).join('')}</div>`;
    }

    updateNewsletterSubscriberStats() {
        const subscribers = Array.isArray(this.subscribers) ? this.subscribers : [];
        const total = subscribers.length;
        const active = subscribers.filter(subscriber => (subscriber.status || 'active') === 'active').length;
        const now = new Date();
        const newThisMonth = subscribers.filter(subscriber => {
            if (!subscriber.subscribed_at) return false;
            const subscribedAt = new Date(subscriber.subscribed_at);
            return !Number.isNaN(subscribedAt.getTime()) &&
                subscribedAt.getFullYear() === now.getFullYear() &&
                subscribedAt.getMonth() === now.getMonth();
        }).length;

        const totalEl = document.getElementById('totalSubscribersStat');
        const activeEl = document.getElementById('activeSubscribers');
        const newMonthEl = document.getElementById('newSubscribersMonth');
        if (totalEl) totalEl.textContent = total;
        if (activeEl) activeEl.textContent = active;
        if (newMonthEl) newMonthEl.textContent = newThisMonth;
    }
    
    async sendNewsletter(isTest = false) {
        const subject = document.getElementById('newsletterSubject').value;
        const content = document.getElementById('newsletterContent').value;
        const recipientType = document.querySelector('input[name="recipientType"]:checked')?.value || 'all';
        
        if (!subject) {
            this.showError('Email subject is required');
            return;
        }
        if (!content) {
            this.showError('Email content is required');
            return;
        }
        
        this.showLoading(true);
        try {
            const formData = new FormData();
            formData.append('subject', subject);
            formData.append('content', content);
            formData.append('recipient_type', isTest ? 'test' : recipientType);
            
            const response = await fetch(`${this.apiBase}?action=send_newsletter`, {
                method: 'POST',
                body: formData
            });
            
            const result = await response.json();
            if (result.success) {
                this.showSuccess(isTest ? 'Test email sent!' : `Newsletter sent to ${result.recipient_count || 0} subscribers!`);
                if (!isTest) {
                    document.getElementById('newsletterSubject').value = '';
                    document.getElementById('newsletterContent').value = '';
                }
            } else {
                throw new Error(result.error || 'Send failed');
            }
        } catch (error) {
            console.error('Error sending newsletter:', error);
            this.showError('Failed to send newsletter: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    // ============================================
    // DOCUMENT METHODS
    // ============================================
    
    renderDocumentsLoading(message = 'Loading compliance documents...') {
        const container = document.getElementById('documentsList');
        const statsContainer = document.getElementById('documentsStats');
        if (statsContainer) {
            statsContainer.innerHTML = `
                <div class="module-card"><h3>Total Records</h3><div class="big">...</div></div>
                <div class="module-card"><h3>Published</h3><div class="big">...</div></div>
                <div class="module-card"><h3>Files Uploaded</h3><div class="big">...</div></div>
                <div class="module-card"><h3>Pending Files</h3><div class="big">...</div></div>
            `;
        }
        if (container) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>${this.escapeHtml(message)}</p></div>`;
        }
    }

    renderDocumentsError(message) {
        const container = document.getElementById('documentsList');
        const statsContainer = document.getElementById('documentsStats');
        if (statsContainer) {
            statsContainer.innerHTML = '';
        }
        if (container) {
            container.innerHTML = `
                <div class="empty-state">
                    <i class="fas fa-triangle-exclamation"></i>
                    <p>${this.escapeHtml(message || 'Unable to load compliance documents right now.')}</p>
                    <div class="service-actions" style="justify-content:center;margin-top:14px;">
                        <button class="btn btn-outline" onclick="komaginAdmin.loadDocuments(true)"><i class="fas fa-rotate-right"></i> Retry</button>
                        <button class="btn btn-primary" onclick="komaginAdmin.openDocumentForm()"><i class="fas fa-plus"></i> Add Document</button>
                    </div>
                </div>
            `;
        }
    }

    async loadDocuments(force = false) {
        if (this.documentsLoading) return;
        if (!force && this.documentsLoaded) {
            this.displayDocuments();
            return;
        }

        this.documentsLoading = true;
        this.renderDocumentsLoading();

        try {
            const response = await fetch(`${this.apiBase}?action=documents_get_all`);
            const result = await response.json();
            if (!result.success) {
                throw new Error(result.error || 'Unable to load compliance documents');
            }
            this.documents = Array.isArray(result.data) ? result.data : [];
            this.documentsStats = result.stats || {};
            this.documentsLoaded = true;
            this.displayDocuments();
        } catch (error) {
            console.error('Error loading documents:', error);
            this.documentsLoaded = false;
            this.renderDocumentsError(error.message);
        } finally {
            this.documentsLoading = false;
        }
    }
    
    displayDocuments() {
        const container = document.getElementById('documentsList');
        const statsContainer = document.getElementById('documentsStats');
        if (!container) return;

        if (statsContainer) {
            const stats = this.documentsStats || {};
            statsContainer.innerHTML = `
                <div class="module-card"><h3>Total Records</h3><div class="big">${this.escapeHtml(String(stats.total ?? 0))}</div></div>
                <div class="module-card"><h3>Published</h3><div class="big">${this.escapeHtml(String(stats.published ?? 0))}</div></div>
                <div class="module-card"><h3>Files Uploaded</h3><div class="big">${this.escapeHtml(String(stats.with_files ?? 0))}</div></div>
                <div class="module-card"><h3>Pending Files</h3><div class="big">${this.escapeHtml(String(stats.placeholders ?? 0))}</div></div>
            `;
        }

        if (!this.documents || this.documents.length === 0) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-file-shield"></i><p>No compliance documents yet. Add the first document record to get started.</p></div>`;
            return;
        }

        container.innerHTML = `<div class="doc-card-list">${this.documents.map(doc => {
            const hasFile = !!doc.filename;
            const icon = this.escapeHtml(doc.icon || 'fa-file-alt');
            const category = this.label(doc.category || 'legal');
            const fileBadge = hasFile
                ? `<span class="doc-row-badge doc-badge-live"><i class="fas fa-file-circle-check"></i> Live file</span>`
                : `<span class="doc-row-badge doc-badge-pending"><i class="fas fa-hourglass-half"></i> Pending</span>`;
            const visBadge = doc.is_visible == 1
                ? `<span class="doc-row-badge doc-badge-vis"><i class="fas fa-eye"></i> Visible</span>`
                : `<span class="doc-row-badge doc-badge-hid"><i class="fas fa-eye-slash"></i> Hidden</span>`;
            const dlBadge = doc.allow_public_download == 1
                ? `<span class="doc-row-badge" style="background:rgba(39,174,96,0.12);color:#1d8a4a;border:1px solid rgba(39,174,96,0.25);"><i class="fas fa-download"></i> Download OK</span>`
                : `<span class="doc-row-badge" style="background:rgba(231,76,60,0.10);color:#c0392b;border:1px solid rgba(231,76,60,0.22);"><i class="fas fa-lock"></i> Request Only</span>`;
            const fileInfo = hasFile
                ? `<div class="doc-row-file"><i class="fas fa-paperclip"></i>${this.escapeHtml(doc.filename)}${doc.file_size ? ` <span class="doc-file-separator">&bull;</span> ${this.formatFileSize(doc.file_size)}` : ''}</div>`
                : `<div class="doc-row-file"><i class="fas fa-exclamation-circle"></i>No file uploaded yet</div>`;
            return `<div class="doc-row-card">
                <div class="doc-row-icon"><i class="fas ${icon}"></i></div>
                <div class="doc-row-info">
                    <div class="doc-row-top">
                        <span class="doc-row-title">${this.escapeHtml(doc.title || 'Document')}</span>
                        <span class="doc-row-badge doc-badge-cat">${this.escapeHtml(category)}</span>
                        ${fileBadge}
                        ${visBadge}
                        ${dlBadge}
                    </div>
                    ${doc.summary ? `<div class="doc-row-summary">${this.escapeHtml(doc.summary)}</div>` : ''}
                    ${fileInfo}
                </div>
                <div class="doc-row-actions">
                    ${this.iconButton({ variant: 'btn-outline btn-xs', icon: 'fa-pen-to-square', label: 'Edit document', attrs: `onclick="komaginAdmin.openDocumentForm('${this.escapeHtml(doc.id)}')"` })}
                    ${this.iconButton({ variant: 'btn-primary btn-xs', icon: 'fa-upload', label: 'Upload document file', attrs: `onclick="komaginAdmin.openDocumentUpload('${this.escapeHtml(doc.id)}')"` })}
                    ${hasFile ? this.iconButton({ action: 'link', variant: 'btn-success btn-xs', icon: 'fa-file-lines', label: 'Open document file', attrs: `href="${this.escapeHtml(doc.file_url)}" target="_blank" rel="noopener"` }) : ''}
                    ${hasFile ? this.iconButton({ variant: 'btn-outline btn-xs', icon: 'fa-unlink', label: 'Remove document file', attrs: `onclick="komaginAdmin.removeDocumentFile('${this.escapeHtml(doc.id)}')"` }) : ''}
                    ${this.iconButton({ variant: 'btn-danger btn-xs', icon: 'fa-trash', label: 'Delete document', attrs: `onclick="komaginAdmin.deleteDocumentRecord('${this.escapeHtml(doc.id)}')"` })}
                </div>
            </div>`;
        }).join('')}</div>`;
    }

    openDocumentForm(id = null) {
        const doc = id ? (this.documents || []).find(item => item.id === id) || {} : {};
        document.getElementById('upgradeModalTitle').textContent = id ? 'Edit Compliance Document' : 'Add Compliance Document';
        document.getElementById('upgradeModalBody').innerHTML = `
            <form id="documentRecordForm">
                <input type="hidden" name="id" value="${this.escapeHtml(doc.id || '')}">
                <div class="form-grid">
                    <div class="form-group"><label>Document Title *</label><input name="title" required value="${this.escapeHtml(doc.title || '')}" placeholder="e.g. Business Registration Certificate"></div>
                    <div class="form-group"><label>Document Key</label><input name="type" value="${this.escapeHtml(doc.type || '')}" placeholder="Auto-generated if left blank"></div>
                    <div class="form-group"><label>Category</label><select name="category"><option value="legal" ${String(doc.category || 'legal') === 'legal' ? 'selected' : ''}>Legal</option><option value="compliance" ${String(doc.category || '') === 'compliance' ? 'selected' : ''}>Compliance</option><option value="governance" ${String(doc.category || '') === 'governance' ? 'selected' : ''}>Governance</option><option value="policy" ${String(doc.category || '') === 'policy' ? 'selected' : ''}>Policy</option></select></div>
                    <div class="form-group"><label>Icon</label><input name="icon" value="${this.escapeHtml(doc.icon || 'fa-file-alt')}" placeholder="fa-file-alt"></div>
                    <div class="form-group"><label>Display Order</label><input type="number" name="sort_order" value="${this.escapeHtml(String(doc.sort_order ?? 0))}"></div>
                    <div class="form-group"><label>Visibility</label><select name="is_visible"><option value="1" ${String(doc.is_visible ?? 1) === '1' ? 'selected' : ''}>Visible</option><option value="0" ${String(doc.is_visible ?? 1) === '0' ? 'selected' : ''}>Hidden</option></select></div>
                    <div class="form-group"><label>Public Download</label><select name="allow_public_download"><option value="1" ${String(doc.allow_public_download ?? 0) === '1' ? 'selected' : ''}>Allow Download</option><option value="0" ${String(doc.allow_public_download ?? 0) === '0' ? 'selected' : ''}>Request Required</option></select><small class="form-hint" style="margin-top:4px;display:block;">When set to Request Required, the public download button becomes a contact request link.</small></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Summary</label><textarea name="summary" rows="5" placeholder="Short public-facing explanation for this legal or compliance document">${this.escapeHtml(doc.summary || '')}</textarea></div>
                    <div class="form-group" style="grid-column:1/-1;"><label>Current File</label><div class="document-current-file">${doc.filename ? `${this.escapeHtml(doc.filename)}${doc.file_size ? ` <span class="doc-file-separator">&bull;</span> ${this.formatFileSize(doc.file_size)}` : ''}` : 'No file uploaded yet. Save the record first or use the upload button after saving.'}</div></div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Document</button>
                </div>
            </form>
        `;
        document.getElementById('documentRecordForm').addEventListener('submit', event => {
            event.preventDefault();
            this.saveDocumentRecord(new FormData(event.target));
        });
        this.showModal('upgradeModal');
    }

    async saveDocumentRecord(formData) {
        const payload = Object.fromEntries(formData.entries());
        this.showLoading(true);
        try {
            const result = await fetch(`${this.apiBase}?action=document_save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'Document could not be saved');
            this.showSuccess(result.message || 'Document saved');
            this.hideModal('upgradeModal');
            await this.loadDocuments(true);
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    }

    openDocumentUpload(id) {
        const doc = (this.documents || []).find(item => item.id === id);
        if (!doc) return this.showError('Document record not found');
        document.getElementById('upgradeModalTitle').textContent = `Upload File - ${doc.title || 'Document'}`;
        document.getElementById('upgradeModalBody').innerHTML = `
            <form id="documentUploadForm">
                <input type="hidden" name="id" value="${this.escapeHtml(doc.id)}">
                <input type="hidden" name="type" value="${this.escapeHtml(doc.type || '')}">
                <div class="form-grid">
                    <div class="form-group" style="grid-column:1/-1;">
                        <label>Select File</label>
                        <input type="file" id="documentUploadInput" name="document" accept=".pdf,.doc,.docx,.jpg,.jpeg,.png" required>
                        <div class="document-upload-note" id="documentUploadNote">No file selected</div>
                        <small>Allowed types: PDF, DOC, DOCX, JPG, JPEG, PNG. Maximum size: 10MB.</small>
                    </div>
                </div>
                <div class="form-actions">
                    <button type="button" class="btn btn-secondary" onclick="komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload File</button>
                </div>
            </form>
        `;
        const uploadInput = document.getElementById('documentUploadInput');
        const uploadNote = document.getElementById('documentUploadNote');
        if (uploadInput && uploadNote) {
            uploadInput.addEventListener('change', () => {
                const file = uploadInput.files && uploadInput.files[0];
                uploadNote.textContent = file ? file.name : 'No file selected';
            });
        }
        document.getElementById('documentUploadForm').addEventListener('submit', event => {
            event.preventDefault();
            this.uploadDocumentFile(new FormData(event.target));
        });
        this.showModal('upgradeModal');
    }

    async uploadDocumentFile(formData) {
        this.showLoading(true);
        try {
            const result = await fetch(`${this.apiBase}?action=upload_document`, {
                method: 'POST',
                body: formData
            }).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'Upload failed');
            this.showSuccess(result.message || 'Document uploaded');
            this.hideModal('upgradeModal');
            await this.loadDocuments(true);
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    }

    async removeDocumentFile(id) {
        this.showStyledConfirm({
            title: 'Remove Document File',
            message: 'Remove the uploaded file from this document record? The document entry will remain, but the linked file will be cleared.',
            confirmText: 'Remove File',
            confirmClass: 'btn-danger',
            onConfirm: async () => {
                this.hideModal('confirmModal');
                this.showLoading(true);
                try {
                    const result = await fetch(`${this.apiBase}?action=document_remove_file&id=${encodeURIComponent(id)}`).then(r => r.json());
                    if (!result.success) throw new Error(result.error || 'File could not be removed');
                    this.showSuccess(result.message || 'File removed');
                    await this.loadDocuments(true);
                } catch (error) {
                    this.showError(error.message);
                } finally {
                    this.showLoading(false);
                }
            }
        });
    }

    async deleteDocumentRecord(id) {
        this.showStyledConfirm({
            title: 'Delete Document Record',
            message: 'Delete this compliance document record completely? This action cannot be undone.',
            confirmText: 'Delete Record',
            confirmClass: 'btn-danger',
            onConfirm: async () => {
                this.hideModal('confirmModal');
                this.showLoading(true);
                try {
                    const result = await fetch(`${this.apiBase}?action=delete_document&id=${encodeURIComponent(id)}`).then(r => r.json());
                    if (!result.success) throw new Error(result.error || 'Document could not be deleted');
                    this.showSuccess(result.message || 'Document deleted');
                    await this.loadDocuments(true);
                } catch (error) {
                    this.showError(error.message);
                } finally {
                    this.showLoading(false);
                }
            }
        });
    }
    
    // ============================================
    // SETTINGS METHODS
    // ============================================
    
    async loadSettings() {
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=get_settings`),
                'Settings could not be loaded'
            );
            if (!result.success) {
                throw new Error(result.error || 'Settings could not be loaded');
            }
            this.settings = result.data || {};
            this.displaySettings();
        } catch (error) {
            console.error('Error loading settings:', error);
            this.showError(error.message || 'Settings could not be loaded');
        }
    }
    
    displaySettings() {
        document.getElementById('companyName').value = this.settings.store_name || '';
        document.getElementById('companyEmail').value = this.settings.store_email || '';
        document.getElementById('secondaryEmail').value = this.settings.secondary_email || '';
        document.getElementById('companyPhone').value = this.settings.store_phone || '';
        document.getElementById('whatsappNumber').value = this.settings.whatsapp_number || '';
        document.getElementById('whatsappUrl').value = this.settings.whatsapp_url || '';
        document.getElementById('companyAddress').value = this.settings.store_address || '';
        document.getElementById('officeMapUrl').value = this.settings.office_map_url || '';
        document.getElementById('businessHours').value = this.settings.business_hours || '';
        document.getElementById('facebookUrl').value = this.settings.facebook || '';
        document.getElementById('footerWhatsappUrl').value = this.settings.whatsapp_url || '';
        document.getElementById('youtubeUrl').value = this.settings.youtube || this.settings.youtube_url || '';
        document.getElementById('linkedinUrl').value = this.settings.linkedin || '';
        document.getElementById('twitterUrl').value = this.settings.twitter || '';
        document.getElementById('instagramUrl').value = this.settings.instagram || '';
        document.getElementById('footerTagline').value = this.settings.footer_tagline || '';
        document.getElementById('emailTransport').value = this.settings.email_transport || 'php_mail';
        document.getElementById('smtpHost').value = this.settings.smtp_host || '';
        document.getElementById('smtpPort').value = this.settings.smtp_port || '587';
        document.getElementById('smtpEncryption').value = this.settings.smtp_encryption || 'tls';
        document.getElementById('smtpUsername').value = this.settings.smtp_username || '';
        document.getElementById('smtpPassword').value = this.settings.smtp_password || '';
        document.getElementById('smtpFromEmail').value = this.settings.smtp_from_email || '';
        document.getElementById('smtpFromName').value = this.settings.smtp_from_name || '';
        document.getElementById('smtpReplyTo').value = this.settings.smtp_reply_to || '';
        document.getElementById('smtpTestRecipient').value = this.settings.smtp_test_recipient || '';
        const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value || ''; };
        set('heroBadgeText', this.settings.hero_badge_text || '');
        set('heroTitleLine1', this.settings.hero_title_line_1 || '');
        set('heroTitleLine2', this.settings.hero_title_line_2 || '');
        set('heroTitleLine3', this.settings.hero_title_line_3 || '');
        set('heroDescriptionText', this.settings.hero_description || '');
        set('heroPrimaryLabel', this.settings.hero_primary_label || '');
        set('heroPrimaryTarget', this.settings.hero_primary_target || '');
        set('heroSecondaryLabel', this.settings.hero_secondary_label || '');
        set('heroSecondaryTarget', this.settings.hero_secondary_target || '');
        set('missionTitle', this.settings.mission_title || '');
        set('missionTextInput', this.settings.mission_text || '');
        set('visionTitle', this.settings.vision_title || '');
        set('visionTextInput', this.settings.vision_text || '');
        set('aboutPageTitleInput', this.settings.about_page_title || '');
        set('aboutPageSubtitleInput', this.settings.about_page_subtitle || '');
        set('aboutStoryLabelInput', this.settings.about_story_label || '');
        set('aboutStoryTitleInput', this.settings.about_story_title || '');
        set('aboutStoryContentInput', this.settings.about_story_content || '');
        const heroImagePath = document.getElementById('heroImagePath');
        const ctaImagePath = document.getElementById('ctaImagePath');
        const heroImages = this.normalizeManagedImageList(this.settings.hero_background_images);
        const leadHeroImage = this.settings.hero_background_image || heroImages[0] || '';
        if (heroImagePath) heroImagePath.value = leadHeroImage;
        if (ctaImagePath) ctaImagePath.value = this.settings.cta_background_image || '';
        if (leadHeroImage) {
            this.updateImagePreview('hero', this.resolveAdminAssetUrl(leadHeroImage), 'Hero image');
        } else {
            this.resetImagePreview('hero');
        }
        this.setHeroSlideImages(this.getAdditionalHeroSlideImages(heroImages, leadHeroImage));
        if (this.settings.cta_background_image) {
            this.updateImagePreview('cta', this.resolveAdminAssetUrl(this.settings.cta_background_image), 'Consultation background image');
        } else {
            this.resetImagePreview('cta');
        }
        this.updateEmailVerificationState();
    }
    
    updateEmailVerificationState() {
        const badge = document.getElementById('emailVerificationStatusBadge');
        const checkedAt = document.getElementById('emailVerificationCheckedAt');
        const message = document.getElementById('emailVerificationMessage');
        if (!badge || !checkedAt || !message) return;
        const status = String(this.settings.email_verification_status || 'pending').toLowerCase();
        const badgeClass = status === 'verified'
            ? 'status-approved'
            : (status === 'failed' ? 'status-rejected' : (status === 'incomplete' ? 'status-pending' : 'status-pending'));
        const labelMap = {
            verified: 'Verified',
            failed: 'Verification failed',
            incomplete: 'Configuration incomplete',
            pending: 'Pending verification'
        };
        badge.className = `status-badge ${badgeClass}`;
        badge.textContent = labelMap[status] || 'Pending verification';
        checkedAt.textContent = this.settings.email_last_verified_at ? this.formatDate(this.settings.email_last_verified_at) : 'Not verified yet';
        message.textContent = this.settings.email_verification_message || 'Complete the email delivery settings and run verification.';
    }

    async saveSettings(showFeedback = true) {
        const resolvedWhatsappUrl = document.getElementById('footerWhatsappUrl').value.trim()
            || document.getElementById('whatsappUrl').value.trim();
        const resolvedYoutubeUrl = document.getElementById('youtubeUrl').value.trim();
        const data = {
            store_name: document.getElementById('companyName').value.trim(),
            store_email: document.getElementById('companyEmail').value.trim(),
            secondary_email: document.getElementById('secondaryEmail').value.trim(),
            store_phone: document.getElementById('companyPhone').value.trim(),
            whatsapp_number: document.getElementById('whatsappNumber').value.trim(),
            whatsapp_url: resolvedWhatsappUrl,
            store_address: document.getElementById('companyAddress').value.trim(),
            office_map_url: document.getElementById('officeMapUrl').value.trim(),
            business_hours: document.getElementById('businessHours').value.trim(),
            facebook: document.getElementById('facebookUrl').value.trim(),
            youtube: resolvedYoutubeUrl,
            youtube_url: resolvedYoutubeUrl,
            linkedin: document.getElementById('linkedinUrl').value.trim(),
            twitter: document.getElementById('twitterUrl').value.trim(),
            instagram: document.getElementById('instagramUrl').value.trim(),
            footer_tagline: document.getElementById('footerTagline').value.trim(),
            email_transport: document.getElementById('emailTransport').value.trim(),
            smtp_host: document.getElementById('smtpHost').value.trim(),
            smtp_port: document.getElementById('smtpPort').value.trim(),
            smtp_encryption: document.getElementById('smtpEncryption').value.trim(),
            smtp_username: document.getElementById('smtpUsername').value.trim(),
            smtp_password: document.getElementById('smtpPassword').value.trim(),
            smtp_from_email: document.getElementById('smtpFromEmail').value.trim(),
            smtp_from_name: document.getElementById('smtpFromName').value.trim(),
            smtp_reply_to: document.getElementById('smtpReplyTo').value.trim(),
            smtp_test_recipient: document.getElementById('smtpTestRecipient').value.trim()
        };
        
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=update_settings`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }),
                'Settings could not be saved'
            );
            if (!result.success) throw new Error(result.error || 'Save failed');
            this.settings = { ...this.settings, ...data };
            if (showFeedback) this.showSuccess('Settings saved successfully!');
        } catch (error) {
            console.error('Error saving settings:', error);
            if (showFeedback) this.showError('Failed to save settings: ' + error.message);
            throw error;
        } finally {
            this.showLoading(false);
        }
    }

    async testEmailConfiguration() {
        this.showLoading(true);
        try {
            const response = await fetch(`${this.apiBase}?action=email_test_configuration`);
            const result = await response.json();
            if (!result.success) throw new Error(result.error || 'Email verification failed');
            await this.loadSettings();
            this.showSuccess(result.message || 'Email connection verified');
        } catch (error) {
            await this.loadSettings();
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    // ============================================
    // PROFILE METHODS
    // ============================================
    
    async loadProfileData() {
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=get_profile`),
                'Profile data could not be loaded'
            );
            if (!result.success) throw new Error(result.error || 'Profile data could not be loaded');
            const user = result.user || {};
            const usernameInput = document.getElementById('profileUsername');
            const emailInput = document.getElementById('profileEmail');
            const createdInput = document.getElementById('profileCreated');
            const lastLoginInput = document.getElementById('profileLastLogin');
            if (usernameInput) usernameInput.value = user.username || '';
            if (emailInput) emailInput.value = user.email || '';
            if (createdInput) createdInput.value = user.created_at ? this.formatDateTime(user.created_at) : '';
            if (lastLoginInput) lastLoginInput.value = user.last_login ? this.formatDateTime(user.last_login) : 'Never';

            const adminNameSpan = document.getElementById('adminUsername');
            if (adminNameSpan) adminNameSpan.textContent = user.username || 'Administrator';
        } catch (error) {
            console.error('Error loading profile:', error);
            this.showError(error.message || 'Profile data could not be loaded');
        }
    }
    
    async updateProfile() {
        const username = document.getElementById('profileUsername').value.trim();
        const email = document.getElementById('profileEmail').value.trim();
        
        if (!username) {
            this.showError('Username is required');
            return;
        }
        if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
            this.showError('Enter a valid email address');
            return;
        }
        
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=update_profile`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ username, email })
                }),
                'Profile update failed'
            );
            if (!result.success) throw new Error(result.error || 'Update failed');
            const user = result.user || {};
            const adminNameSpan = document.getElementById('adminUsername');
            if (adminNameSpan && user.username) adminNameSpan.textContent = user.username;
            this.showSuccess(result.message || 'Profile updated successfully!');
            await this.loadProfileData();
        } catch (error) {
            console.error('Error updating profile:', error);
            this.showError('Failed to update profile: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    updatePasswordStrength(password) {
        const bar = document.getElementById('passwordStrengthBar');
        const label = document.getElementById('passwordStrengthLabel');
        if (!bar) return;
        if (!password) {
            bar.className = 'password-strength';
            if (label) label.textContent = 'Start typing to see password strength';
            return;
        }
        let score = 0;
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        let cls = 'password-strength strength-weak';
        let text = 'Weak ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â add numbers, symbols, and uppercase letters';
        if (score >= 4) { cls = 'password-strength strength-strong'; text = 'Strong password'; }
        else if (score >= 2) { cls = 'password-strength strength-medium'; text = 'Medium ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â make it longer or more complex'; }
        bar.className = cls;
        if (label) label.textContent = text;
    }

    async changePassword() {
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        
        if (!currentPassword || !newPassword || !confirmPassword) {
            this.showError('All password fields are required');
            return;
        }
        
        if (newPassword.length < 6) {
            this.showError('Password must be at least 6 characters');
            return;
        }
        
        if (newPassword !== confirmPassword) {
            this.showError('New passwords do not match');
            return;
        }
        
        this.showLoading(true);
        try {
            const response = await fetch(`${this.apiBase}?action=change_password`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    current_password: currentPassword,
                    new_password: newPassword,
                    confirm_password: confirmPassword
                })
            });
            const result = await response.json();
            if (result.success) {
                this.showSuccess('Password changed successfully!');
                document.getElementById('passwordForm').reset();
                document.getElementById('passwordStrengthBar').className = 'password-strength';
            } else {
                throw new Error(result.error || 'Change failed');
            }
        } catch (error) {
            console.error('Error changing password:', error);
            this.showError('Failed to change password: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }

    updatePasswordStrength(password) {
        const bar = document.getElementById('passwordStrengthBar');
        const label = document.getElementById('passwordStrengthLabel');
        if (!bar) return;
        if (!password) {
            bar.className = 'password-strength';
            if (label) label.textContent = 'Start typing to see password strength';
            return;
        }
        let score = 0;
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^A-Za-z0-9]/.test(password)) score++;
        let cls = 'password-strength strength-weak';
        let text = 'Weak - add numbers, symbols, and uppercase letters';
        if (score >= 4) {
            cls = 'password-strength strength-strong';
            text = 'Strong password';
        } else if (score >= 2) {
            cls = 'password-strength strength-medium';
            text = 'Medium - make it longer or more complex';
        }
        bar.className = cls;
        if (label) label.textContent = text;
    }

    async changePassword() {
        const currentPassword = document.getElementById('currentPassword').value;
        const newPassword = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;

        if (!currentPassword || !newPassword || !confirmPassword) {
            this.showError('All password fields are required');
            return;
        }

        if (newPassword.length < 6) {
            this.showError('Password must be at least 6 characters');
            return;
        }

        if (newPassword !== confirmPassword) {
            this.showError('New passwords do not match');
            return;
        }

        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=change_password`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        current_password: currentPassword,
                        new_password: newPassword,
                        confirm_password: confirmPassword
                    })
                }),
                'Password change failed'
            );
            if (!result.success) throw new Error(result.error || 'Change failed');
            this.showSuccess(result.message || 'Password changed successfully!');
            document.getElementById('passwordForm').reset();
            document.getElementById('passwordStrengthBar').className = 'password-strength';
            const label = document.getElementById('passwordStrengthLabel');
            if (label) label.textContent = 'Start typing to see password strength';
        } catch (error) {
            console.error('Error changing password:', error);
            this.showError('Failed to change password: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    // ============================================
    // IMAGE UPLOAD METHODS
    // ============================================
    
    async handleImageUpload(file, type) {
        if (!file) return;
        
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!allowedTypes.includes(file.type.toLowerCase())) {
            this.showError('Invalid file type. Please select a JPEG, PNG, GIF, or WebP image.');
            return;
        }
        
        const maxSize = 5 * 1024 * 1024;
        if (file.size > maxSize) {
            this.showError('File too large. Maximum size: 5MB');
            return;
        }
        
        const formData = new FormData();
        formData.append('image', file);
        
        this.showLoading(true);
        try {
            const response = await fetch(`${this.apiBase}?action=upload_image`, {
                method: 'POST',
                body: formData
            });
            const result = await response.json();
            if (result.success) {
                this.updateImagePreview(type, result.file_url, 'Preview');
                document.getElementById(`${type}ImagePath`).value = result.file_path;
                this.showSuccess('Image uploaded successfully!');
            } else {
                throw new Error(result.error || 'Upload failed');
            }
        } catch (error) {
            console.error('Error uploading image:', error);
            this.showError('Failed to upload image: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    }
    
    updateImagePreview(type, imageUrl, altText) {
        const preview = document.getElementById(`${type}ImagePreview`);
        if (preview) {
            preview.innerHTML = `<img src="${imageUrl}" alt="${this.escapeHtml(altText)}">`;
            preview.classList.add('has-image');
        }
    }
    
    resetImagePreview(type) {
        const preview = document.getElementById(`${type}ImagePreview`);
        if (preview) {
            const icon = ['testimonial', 'team'].includes(type) ? 'fa-user' : 'fa-image';
            preview.innerHTML = `<i class="fas ${icon}"></i><span>No image selected</span>`;
            preview.classList.remove('has-image');
        }
    }

    iconButton({ action = 'button', variant = 'btn-outline', icon = 'fa-pen-to-square', label = 'Action', attrs = '' } = {}) {
        const tag = action === 'link' ? 'a' : 'button';
        const safeLabel = this.escapeHtml(label);
        const safeAttrs = attrs || '';
        return `<${tag} class="btn btn-sm ${variant} icon-btn" title="${safeLabel}" aria-label="${safeLabel}" ${safeAttrs}><i class="fas ${icon}" aria-hidden="true"></i></${tag}>`;
    }

    resolveAdminAssetUrl(path) {
        const raw = String(path || '').trim();
        if (!raw) return '';
        if (/^(https?:)?\/\//.test(raw) || raw.startsWith('/')) return raw;
        if (raw.startsWith('uploads/')) return raw;
        if (raw.startsWith('admin/uploads/')) return raw.replace(/^admin\//, '');
        if (raw.startsWith('images/')) return `../${raw}`;
        return `uploads/${raw}`;
    }

    normalizeManagedImageList(raw) {
        let values = raw;
        if (typeof values === 'string') {
            const trimmed = values.trim();
            if (trimmed) {
                try {
                    values = JSON.parse(trimmed);
                } catch (error) {
                    values = trimmed.split(/[\r\n,]+/);
                }
            }
        }
        if (!Array.isArray(values)) return [];
        return Array.from(new Set(values.map(item => String(item || '').trim()).filter(Boolean)));
    }

    setHeroSlideImages(images = []) {
        this.heroSlideImages = this.normalizeManagedImageList(images).slice(0, 5);
        this.renderHeroSlideImages();
    }

    getAdditionalHeroSlideImages(allImages = [], leadImage = '') {
        const normalized = this.normalizeManagedImageList(allImages);
        const lead = String(leadImage || '').trim();
        let skippedLead = false;
        return normalized.filter(image => {
            if (lead && !skippedLead && image === lead) {
                skippedLead = true;
                return false;
            }
            return true;
        }).slice(0, 5);
    }

    renderHeroSlideImages() {
        const container = document.getElementById('heroSlidesManager');
        if (!container) return;
        const primary = document.getElementById('heroImagePath')?.value.trim() || '';
        if (!this.heroSlideImages.length) {
            container.innerHTML = `<div class="empty-state" style="grid-column:1/-1;"><i class="fas fa-images"></i><p>No extra slideshow images yet. Add up to 5 more images for the homepage hero rotation.</p></div>`;
            return;
        }
        container.innerHTML = this.heroSlideImages.map((path, index) => {
            const imageUrl = this.resolveAdminAssetUrl(path);
            return `
                <div class="hero-slide-admin-card">
                    <div class="hero-slide-admin-thumb" style="background-image:url('${this.escapeHtml(imageUrl)}')"></div>
                    <div class="hero-slide-admin-body">
                        <div class="hero-slide-admin-meta">
                            <span>Slide ${index + 2}</span>
                            <span>${this.escapeHtml(path.split('/').pop() || 'Image')}</span>
                        </div>
                        <div class="hero-slide-admin-actions">
                            <button type="button" class="btn btn-sm btn-outline icon-btn" title="Move up" onclick="komaginAdmin.moveHeroSlide(${index}, -1)" ${index === 0 ? 'disabled' : ''}><i class="fas fa-arrow-up"></i></button>
                            <button type="button" class="btn btn-sm btn-outline icon-btn" title="Move down" onclick="komaginAdmin.moveHeroSlide(${index}, 1)" ${index === this.heroSlideImages.length - 1 ? 'disabled' : ''}><i class="fas fa-arrow-down"></i></button>
                            <button type="button" class="btn btn-sm btn-primary icon-btn" title="Make lead" onclick="komaginAdmin.promoteHeroSlide(${index})"><i class="fas fa-star"></i></button>
                            <button type="button" class="btn btn-sm btn-danger icon-btn" title="Remove" onclick="komaginAdmin.removeHeroSlide(${index})"><i class="fas fa-trash"></i></button>
                        </div>
                    </div>
                </div>
            `;
        }).join('');
    }

    async uploadHeroSlideImages(fileList) {
        const files = Array.from(fileList || []).filter(Boolean);
        const input = document.getElementById('heroSlideImageInput');
        if (!files.length) return;
        const remainingSlots = Math.max(0, 5 - this.heroSlideImages.length);
        if (remainingSlots <= 0) {
            this.showError('You already have 5 extra hero slideshow images. Remove one before adding another.');
            if (input) input.value = '';
            return;
        }
        const uploadQueue = files.slice(0, remainingSlots);
        this.showLoading(true);
        try {
            for (const file of uploadQueue) {
                const result = await this.uploadSingleImageAsset(file);
                const path = String(result.file_path || '').trim();
                if (path) {
                    this.heroSlideImages.push(path);
                }
            }
            this.heroSlideImages = this.normalizeManagedImageList(this.heroSlideImages).slice(0, 5);
            this.renderHeroSlideImages();
            this.showSuccess(uploadQueue.length === 1 ? 'Hero slideshow image added' : 'Hero slideshow images added');
        } catch (error) {
            this.showError(error.message || 'Hero slideshow images could not be uploaded');
        } finally {
            if (input) input.value = '';
            this.showLoading(false);
        }
    }

    moveHeroSlide(index, direction) {
        const target = index + direction;
        if (target < 0 || target >= this.heroSlideImages.length) return;
        const items = [...this.heroSlideImages];
        [items[index], items[target]] = [items[target], items[index]];
        this.heroSlideImages = items;
        this.renderHeroSlideImages();
    }

    promoteHeroSlide(index) {
        const path = this.heroSlideImages[index];
        if (!path) return;
        const heroImagePath = document.getElementById('heroImagePath');
        const previousLead = heroImagePath?.value.trim() || '';
        if (heroImagePath) heroImagePath.value = path;
        if (previousLead) {
            this.heroSlideImages[index] = previousLead;
        } else {
            this.heroSlideImages.splice(index, 1);
        }
        this.heroSlideImages = this.normalizeManagedImageList(this.heroSlideImages).slice(0, 5);
        this.updateImagePreview('hero', this.resolveAdminAssetUrl(path), 'Hero image');
        this.renderHeroSlideImages();
    }

    removeHeroSlide(index) {
        if (index < 0 || index >= this.heroSlideImages.length) return;
        this.heroSlideImages.splice(index, 1);
        this.renderHeroSlideImages();
    }
    
    // ============================================
    // DELETE METHODS
    // ============================================
    
    confirmDelete(itemId, type) {
        this.currentItemId = itemId;
        this.currentType = type;
        this.showStyledConfirm({
            title: 'Confirm Deletion',
            message: 'Are you sure you want to delete this item? This action cannot be undone.',
            confirmText: 'Delete',
            confirmClass: 'btn-danger',
            onConfirm: () => this.deleteItemConfirmed()
        });
    }

    showStyledConfirm({ title = 'Confirm Action', message = 'Are you sure you want to continue?', confirmText = 'Confirm', confirmClass = 'btn-primary', onConfirm = null } = {}) {
        const titleEl = document.getElementById('confirmModalTitle');
        const messageEl = document.getElementById('confirmModalMessage');
        const confirmBtn = document.getElementById('confirmDeleteBtn');
        if (titleEl) titleEl.textContent = title;
        if (messageEl) messageEl.textContent = message;
        if (confirmBtn) {
            confirmBtn.textContent = confirmText;
            confirmBtn.className = `btn ${confirmClass}`;
        }
        this.pendingConfirmAction = typeof onConfirm === 'function' ? onConfirm : null;
        this.showModal('confirmModal');
    }

    async executeStyledConfirm() {
        if (typeof this.pendingConfirmAction === 'function') {
            const callback = this.pendingConfirmAction;
            this.pendingConfirmAction = null;
            await callback();
            return;
        }
        await this.deleteItemConfirmed();
    }
    
    async deleteItemConfirmed() {
        if (!this.currentItemId || !this.currentType) return;
        
        this.hideModal('confirmModal');
        this.showLoading(true);
        
        try {
            const response = await fetch(`${this.apiBase}?action=delete_${this.currentType}&id=${this.currentItemId}`, {
                method: 'POST'
            });
            const result = await response.json();
            if (result.success) {
                this.showSuccess(`${this.currentType} deleted successfully!`);
                switch(this.currentType) {
                    case 'project': this.loadProjects(); break;
                    case 'service': this.loadServices(); break;
                    case 'testimonial': this.loadTestimonials(); break;
                    case 'team': this.loadTeam(); break;
                    case 'contact': this.loadContacts(); break;
                }
                this.loadDashboardData();
            } else {
                throw new Error(result.error || 'Delete failed');
            }
        } catch (error) {
            console.error('Error deleting item:', error);
            this.showError('Failed to delete: ' + error.message);
        } finally {
            this.showLoading(false);
            this.currentItemId = null;
            this.currentType = null;
        }
    }
    
    // ============================================
    // UI HELPER METHODS
    // ============================================
    
    showSection(sectionName) {
        document.querySelectorAll('.nav-item').forEach(item => {
            item.classList.remove('active');
            if (item.getAttribute('data-section') === sectionName) {
                item.classList.add('active');
            }
        });
        
        document.querySelectorAll('.content-section').forEach(section => {
            section.classList.remove('active');
        });
        
        const target = document.getElementById(sectionName);
        if (target) target.classList.add('active');
        
        if (window.innerWidth <= 768) {
            window.scrollTo({ top: 0, behavior: 'smooth' });
            this.closeMobileMenu();
        }
    }
    
    showModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('active');
            document.body.style.overflow = 'hidden';
        }
    }
    
    hideModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('active');
            document.body.style.overflow = '';
            if (modalId === 'confirmModal') {
                this.pendingConfirmAction = null;
            }
        }
    }
    
    closeMobileMenu() {
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('sidebarOverlay');
        if (sidebar) sidebar.classList.remove('active');
        if (overlay) overlay.classList.remove('active');
        document.body.style.overflow = '';
    }
    
    showLoading(show) {
        const spinner = document.getElementById('loadingSpinner');
        if (spinner) spinner.classList.toggle('active', show);
        this.isLoading = show;
    }
    
    showError(message) {
        this.showToast(message, 'error');
    }
    
    showSuccess(message) {
        this.showToast(message, 'success');
    }
    
    showToast(message, type = 'info') {
        const existing = document.querySelectorAll('.toast-notification');
        existing.forEach(toast => toast.remove());
        
        const toast = document.createElement('div');
        toast.className = `toast-notification ${type}`;
        toast.innerHTML = `
            <i class="fas fa-${type === 'error' ? 'exclamation-circle' : 'check-circle'}"></i>
            <span>${message}</span>
        `;
        document.body.appendChild(toast);
        
        setTimeout(() => {
            if (toast.parentElement) toast.remove();
        }, 4000);
    }
    
    formatDateTime(dateTimeString) {
        try {
            const date = new Date(dateTimeString);
            if (isNaN(date.getTime())) return 'Invalid Date';
            return date.toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric',
                hour: '2-digit', minute: '2-digit'
            });
        } catch {
            return 'Invalid Date';
        }
    }
    
    formatDate(dateString) {
        try {
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return 'Invalid Date';
            return date.toLocaleDateString('en-US', {
                year: 'numeric', month: 'short', day: 'numeric'
            });
        } catch {
            return 'Invalid Date';
        }
    }
    
    escapeHtml(unsafe) {
        if (!unsafe) return '';
        return unsafe
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
}

window.KomaginAdmin = KomaginAdmin;

// ============================================
// KOMAGIN LIMITED - UPGRADE v2.0 ADMIN MODULES
// ============================================
(function() {
    const oldCheckAuth = KomaginAdmin.prototype.checkAuthStatus;
    KomaginAdmin.prototype.checkAuthStatus = async function() {
        try {
            const response = await fetch(`${this.apiBase}?action=get_session`);
            const result = await response.json();
            if (result.success && result.authenticated) {
                window.ADMIN_ROLE = result.user_role || result.user?.role || 'admin';
                this.adminRole = window.ADMIN_ROLE;
                this.applyRoleVisibility();
                const roleLabel = document.querySelector('.profile-info p');
                if (roleLabel) roleLabel.textContent = 'Website Admin Panel';
                return;
            }
        } catch (error) {}
        return oldCheckAuth.call(this);
    };

    const oldLoadDashboard = KomaginAdmin.prototype.loadDashboardData;
    KomaginAdmin.prototype.loadDashboardData = async function() {
        await oldLoadDashboard.call(this);
        try {
            const response = await fetch(`${this.apiBase}?action=get_stats`);
            const result = await response.json();
            if (result.success) {
                const s = result.data || {};
                this.setText('openJobs', s.open_jobs || 0);
                this.setText('newApplications', s.new_applications || 0);
                this.setText('staffCount', s.staff_count || 0);
                this.setText('openAssets', s.open_assets || 0);
                this.setText('pendingApprovals', s.pending_approvals || 0);
                this.setText('activeBranches', s.active_branches || 0);
            }
        } catch (error) {}
        this.loadPendingApprovals();
        this.loadRecentApplications();
    };

    KomaginAdmin.prototype.setText = function(id, value) {
        const el = document.getElementById(id);
        if (el) el.textContent = value;
    };

    KomaginAdmin.prototype.applyRoleVisibility = function() {
        document.querySelectorAll('.nav-item[data-section]').forEach(item => {
            const li = item.closest('li');
            if (li) li.classList.remove('role-hidden');
        });

        document.querySelectorAll('[data-role-group]').forEach(label => {
            label.classList.remove('role-hidden');
        });

        // Hide role-hidden items completely
        const styleId = 'roleHideStyle';
        if (!document.getElementById(styleId)) {
            const s = document.createElement('style');
            s.id = styleId;
            s.textContent = '.role-hidden{display:none!important}';
            document.head.appendChild(s);
        }

        const banner = document.getElementById('hrRedirectBanner');
        if (banner) {
            banner.remove();
        }
        const mainContent = document.querySelector('.main-content');
        if (mainContent) {
            mainContent.style.paddingTop = '';
        }
    };

    const oldLoadSectionData = KomaginAdmin.prototype.loadSectionData;
    KomaginAdmin.prototype.loadSectionData = function(section) {
        if (section === 'partners') {
            return Promise.all([
                this.loadUpgradeSection('partners'),
                this.loadPartnerShowcaseManager()
            ]);
        }
        if (section === 'documents') {
            this.loadDocuments(true);
            this.loadSettings();
            return;
        }
        const map = this.getUpgradeConfig();
        if (map[section]) return this.loadUpgradeSection(section);
        if (section === 'files-manager') return this.loadFilesManager();
        return oldLoadSectionData.call(this, section);
    };

    const oldInit = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInit.call(this);
        document.addEventListener('click', (event) => {
            const addBtn = event.target.closest('[data-upgrade-add]');
            if (addBtn) this.openUpgradeForm(addBtn.dataset.upgradeAdd);
            const close = event.target.closest('#closeUpgradeModal');
            if (close) this.hideModal('upgradeModal');
            const enquiryAction = event.target.closest('[data-partner-enquiry-action]');
            if (enquiryAction) {
                const action = enquiryAction.getAttribute('data-partner-enquiry-action');
                const id = enquiryAction.getAttribute('data-record-id') || '';
                if (!id) return;
                if (action === 'detail') this.openPartnerEnquiryDetail(id);
                if (action === 'edit') this.openPartnerEnquiryEditor(id);
                if (action === 'approve') this.runUpgradeAction(`partners_update_status&id=${id}`, { status: 'approved' }, 'partners');
                if (action === 'reject') this.openPartnerRejectionForm(id);
                if (action === 'delete') this.deletePartnerEnquiry(id);
                if (action === 'nda') this.generateDocument(`partners_generate_nda&partner_id=${id}`);
            }
            const showcaseAction = event.target.closest('[data-partner-showcase-action]');
            if (showcaseAction) {
                const action = showcaseAction.getAttribute('data-partner-showcase-action');
                const id = showcaseAction.getAttribute('data-record-id') || '';
                if (!id) return;
                if (action === 'edit') this.openPartnerShowcaseEditor(id);
                if (action === 'delete') this.deletePartnerShowcaseEntry(id);
            }
        });
        const addPartnerShowcaseBtn = document.getElementById('addPartnerShowcaseBtn');
        if (addPartnerShowcaseBtn && !addPartnerShowcaseBtn.dataset.bound) {
            addPartnerShowcaseBtn.dataset.bound = '1';
            addPartnerShowcaseBtn.addEventListener('click', () => this.openPartnerShowcaseEditor());
        }
        const partnerNdaTemplateForm = document.getElementById('partnerNdaTemplateForm');
        if (partnerNdaTemplateForm && !partnerNdaTemplateForm.dataset.bound) {
            partnerNdaTemplateForm.dataset.bound = '1';
            partnerNdaTemplateForm.addEventListener('submit', (event) => {
                event.preventDefault();
                this.savePartnerNdaTemplate();
            });
        }
        const genPayroll = document.getElementById('generatePayrollBtn');
        if (genPayroll) genPayroll.addEventListener('click', () => this.generatePayroll());
        const period = document.getElementById('payrollPeriod');
        if (period) period.value = new Date().toISOString().slice(0, 7);
        const saveGovernanceBtn = document.getElementById('saveGovernanceBtn');
        if (saveGovernanceBtn) saveGovernanceBtn.addEventListener('click', () => this.saveGovernanceContent());
    };

    const oldDisplaySettings = KomaginAdmin.prototype.displaySettings;
    KomaginAdmin.prototype.displaySettings = function() {
        oldDisplaySettings.call(this);
        const set = (id, value) => { const el = document.getElementById(id); if (el) el.value = value || ''; };
        set('defaultCurrency', this.settings.default_currency || 'PGK');
        set('partnerPortal', this.settings.partner_portal || 'enabled');
        set('hrAdminEmail', this.settings.hr_admin_email || 'hr@komagin.com');
        set('heroBadgeText', this.settings.hero_badge_text || '');
        set('heroTitleLine1', this.settings.hero_title_line_1 || '');
        set('heroTitleLine2', this.settings.hero_title_line_2 || '');
        set('heroTitleLine3', this.settings.hero_title_line_3 || '');
        set('heroDescriptionText', this.settings.hero_description || '');
        set('heroPrimaryLabel', this.settings.hero_primary_label || 'Our Projects');
        set('heroPrimaryTarget', this.settings.hero_primary_target || 'projects');
        set('heroSecondaryLabel', this.settings.hero_secondary_label || 'Request Consultation');
        set('heroSecondaryTarget', this.settings.hero_secondary_target || 'contact');
        set('missionTitle', this.settings.mission_title || 'Our Mission');
        set('missionTextInput', this.settings.mission_text || '');
        set('visionTitle', this.settings.vision_title || 'Our Vision');
        set('visionTextInput', this.settings.vision_text || '');
        set('aboutPageTitleInput', this.settings.about_page_title || 'About Komagin Limited');
        set('aboutPageSubtitleInput', this.settings.about_page_subtitle || '');
        set('aboutStoryLabelInput', this.settings.about_story_label || 'OUR STORY');
        set('aboutStoryTitleInput', this.settings.about_story_title || 'Company History');
        set('aboutStoryContentInput', this.settings.about_story_content || '');
        set('partnerNdaDocumentTitle', this.settings.partner_nda_document_title || 'Non-Disclosure Agreement');
        set('partnerNdaIntroText', this.settings.partner_nda_intro_text || '');
        set('partnerNdaPurposeText', this.settings.partner_nda_purpose_text || '');
        set('partnerNdaConfidentialText', this.settings.partner_nda_confidential_text || '');
        set('partnerNdaObligationsText', this.settings.partner_nda_obligations_text || '');
        set('partnerNdaExclusionsText', this.settings.partner_nda_exclusions_text || '');
        set('partnerNdaDurationText', this.settings.partner_nda_duration_text || '');
        set('partnerNdaReturnText', this.settings.partner_nda_return_text || '');
        set('partnerNdaAdditionalText', this.settings.partner_nda_additional_text || '');
        set('partnerNdaLeftSignatory', this.settings.partner_nda_left_signatory || '{{komagin_company}}');
        set('partnerNdaRightSignatory', this.settings.partner_nda_right_signatory || '{{partner_company}}');
        set('partnerNdaLeftWitness', this.settings.partner_nda_left_footer || 'Authorized Representative');
        set('partnerNdaRightWitness', this.settings.partner_nda_right_footer || 'Date');
        set('governanceIntroText', this.settings.governance_intro || '');
        set('governanceCommitmentItems', this.settings.governance_commitment_items || '');
        const heroImages = this.normalizeManagedImageList(this.settings.hero_background_images);
        const leadHeroImage = this.settings.hero_background_image || heroImages[0] || 'images/hero-bg.jpeg';
        set('heroImagePath', leadHeroImage);
        set('ctaImagePath', this.settings.cta_background_image || 'images/hero-bg.jpeg');
        if (leadHeroImage) {
            this.updateImagePreview('hero', this.resolveAdminAssetUrl(leadHeroImage), 'Hero image');
        } else {
            this.resetImagePreview('hero');
        }
        this.setHeroSlideImages(this.getAdditionalHeroSlideImages(heroImages, leadHeroImage));
        if (this.settings.cta_background_image) {
            this.updateImagePreview('cta', this.resolveAdminAssetUrl(this.settings.cta_background_image), 'Consultation background image');
        } else {
            this.resetImagePreview('cta');
        }
        this.applyRoleVisibility();
    };

    const oldSaveSettings = KomaginAdmin.prototype.saveSettings;
    KomaginAdmin.prototype.saveSettings = async function(showFeedback = true) {
        const extra = {
            default_currency: document.getElementById('defaultCurrency')?.value.trim() || 'PGK',
            partner_portal: document.getElementById('partnerPortal')?.value || 'enabled',
            hr_admin_email: document.getElementById('hrAdminEmail')?.value.trim() || 'hr@komagin.com',
            hero_background_image: document.getElementById('heroImagePath')?.value.trim() || 'images/hero-bg.jpeg',
            hero_background_images: [document.getElementById('heroImagePath')?.value.trim() || 'images/hero-bg.jpeg', ...(Array.isArray(this.heroSlideImages) ? this.heroSlideImages : [])].filter(Boolean),
            cta_background_image: document.getElementById('ctaImagePath')?.value.trim() || 'images/hero-bg.jpeg',
            hero_badge_text: document.getElementById('heroBadgeText')?.value.trim() || '',
            hero_title_line_1: document.getElementById('heroTitleLine1')?.value.trim() || '',
            hero_title_line_2: document.getElementById('heroTitleLine2')?.value.trim() || '',
            hero_title_line_3: document.getElementById('heroTitleLine3')?.value.trim() || '',
            hero_description: document.getElementById('heroDescriptionText')?.value.trim() || '',
            hero_primary_label: document.getElementById('heroPrimaryLabel')?.value.trim() || 'Our Projects',
            hero_primary_target: document.getElementById('heroPrimaryTarget')?.value.trim() || 'projects',
            hero_secondary_label: document.getElementById('heroSecondaryLabel')?.value.trim() || 'Request Consultation',
            hero_secondary_target: document.getElementById('heroSecondaryTarget')?.value.trim() || 'contact',
            mission_title: document.getElementById('missionTitle')?.value.trim() || 'Our Mission',
            mission_text: document.getElementById('missionTextInput')?.value.trim() || '',
            vision_title: document.getElementById('visionTitle')?.value.trim() || 'Our Vision',
            vision_text: document.getElementById('visionTextInput')?.value.trim() || '',
            about_page_title: document.getElementById('aboutPageTitleInput')?.value.trim() || 'About Komagin Limited',
            about_page_subtitle: document.getElementById('aboutPageSubtitleInput')?.value.trim() || '',
            about_story_label: document.getElementById('aboutStoryLabelInput')?.value.trim() || 'OUR STORY',
            about_story_title: document.getElementById('aboutStoryTitleInput')?.value.trim() || 'Company History',
            about_story_content: document.getElementById('aboutStoryContentInput')?.value.trim() || ''
        };
        await oldSaveSettings.call(this, showFeedback);
        const result = await this.readAdminJsonResponse(
            fetch(`${this.apiBase}?action=update_settings`, {
                method: 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(extra)
            }),
            'Homepage settings could not be saved'
        );
        if (!result.success) {
            throw new Error(result.error || 'Homepage settings could not be saved');
        }
        this.settings = { ...this.settings, ...extra };
    };

    KomaginAdmin.prototype.savePartnerNdaTemplate = async function() {
        const data = {
            partner_nda_document_title: document.getElementById('partnerNdaDocumentTitle')?.value.trim() || 'Non-Disclosure Agreement',
            partner_nda_intro_text: document.getElementById('partnerNdaIntroText')?.value.trim() || '',
            partner_nda_purpose_text: document.getElementById('partnerNdaPurposeText')?.value.trim() || '',
            partner_nda_confidential_text: document.getElementById('partnerNdaConfidentialText')?.value.trim() || '',
            partner_nda_obligations_text: document.getElementById('partnerNdaObligationsText')?.value.trim() || '',
            partner_nda_exclusions_text: document.getElementById('partnerNdaExclusionsText')?.value.trim() || '',
            partner_nda_duration_text: document.getElementById('partnerNdaDurationText')?.value.trim() || '',
            partner_nda_return_text: document.getElementById('partnerNdaReturnText')?.value.trim() || '',
            partner_nda_additional_text: document.getElementById('partnerNdaAdditionalText')?.value.trim() || '',
            partner_nda_left_signatory: document.getElementById('partnerNdaLeftSignatory')?.value.trim() || '{{komagin_company}}',
            partner_nda_right_signatory: document.getElementById('partnerNdaRightSignatory')?.value.trim() || '{{partner_company}}',
            partner_nda_left_footer: document.getElementById('partnerNdaLeftWitness')?.value.trim() || 'Authorized Representative',
            partner_nda_right_footer: document.getElementById('partnerNdaRightWitness')?.value.trim() || 'Date'
        };
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=update_settings`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }),
                'NDA template could not be saved'
            );
            if (!result.success) throw new Error(result.error || 'NDA template could not be saved');
            this.settings = { ...this.settings, ...data };
            this.showSuccess('NDA template saved successfully');
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.saveGovernanceContent = async function() {
        const data = {
            governance_intro: (document.getElementById('governanceIntroText')?.value || '').trim(),
            governance_commitment_items: (document.getElementById('governanceCommitmentItems')?.value || '').trim()
        };
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=update_settings`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                }),
                'Governance content could not be saved'
            );
            if (!result.success) throw new Error(result.error || 'Save failed');
            this.settings = { ...this.settings, ...data };
            this.showSuccess('Governance content saved successfully!');
        } catch (error) {
            this.showError('Failed to save governance content: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.getUpgradeConfig = function() {
        // This deployment admin manages only public website operations.
        return {
            'assets-list': { container:'assetsListContainer', list:'assets_get_all', save:'assets_save', del:'assets_delete', title:'Asset', cardStats:true, columns:[['asset_tag','Asset Tag'],['name','Name'],['category','Category'],['condition','Condition'],['status','Status'],['assigned_staff_name','Assigned To'],['assigned_branch_name','Branch'],['location','Location']], fields:[['asset_tag','Asset Tag','text'],['name','Name','text',true],['category','Category','select',false,['vehicle','equipment','tool','it','furniture','other']],['description','Description','textarea'],['serial_number','Serial Number','text'],['purchase_date','Purchase Date','date'],['purchase_cost','Purchase Cost','number'],['current_value','Current Value','number'],['supplier','Supplier','text'],['warranty_expiry','Warranty Expiry','date'],['condition','Condition','select',false,['excellent','good','fair','poor','decommissioned']],['status','Status','select',false,['available','assigned','maintenance','disposed']],['assigned_to_branch','Assigned Branch','branch_select'],['location','Location','text'],['notes','Notes','textarea']], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Edit" onclick="komaginAdmin.openUpgradeForm('assets-list','${r.id}')"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Assign" onclick="komaginAdmin.quickAssignAsset('${r.id}')"><i class="fas fa-user-check"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Form" onclick="komaginAdmin.generateDocument('assets_generate_assignment&asset_id=${r.id}')"><i class="fas fa-file-lines"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Delete" onclick="komaginAdmin.runUpgradeAction('assets_delete&id=${r.id}',null,'assets-list')"><i class="fas fa-trash"></i></button>` },
            'assets-maintenance': { container:'assetsMaintenanceContainer', list:'maintenance_get_all', save:'maintenance_save', title:'Maintenance', columns:[['asset_tag','Asset Tag'],['asset_name','Asset'],['maintenance_type','Type'],['description','Description'],['scheduled_date','Scheduled'],['status','Status'],['cost','Cost']], fields:[['asset_id','Asset ID','text',true],['maintenance_type','Type','select',false,['scheduled','repair','inspection','upgrade']],['description','Description','textarea',true],['cost','Cost','number'],['performed_by','Performed By','text'],['scheduled_date','Scheduled Date','date'],['next_maintenance_date','Next Maintenance Date','date'],['status','Status','select',false,['scheduled','in_progress','completed','cancelled']],['notes','Notes','textarea']], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Edit" onclick="komaginAdmin.openUpgradeForm('assets-maintenance','${r.id}')"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-success icon-btn" title="Complete" onclick="komaginAdmin.runUpgradeAction('maintenance_complete&id=${r.id}',null,'assets-maintenance')"><i class="fas fa-check"></i></button>` },
            'branches-list': { container:'branchesListContainer', list:'branches_get_all', save:'branches_save', del:'branches_delete', title:'Branch', card:true, columns:[['name','Branch Name'],['branch_code','Code'],['region','Region'],['manager_name','Manager'],['status','Status'],['registered_at','Registered']], fields:[['branch_code','Branch Code','text'],['name','Name','text',true],['region','Region','text'],['country','Country','text'],['address','Address','textarea'],['phone','Phone','text'],['email','Email','email'],['manager_name','Manager Name','text'],['manager_username','Branch Manager Login ID','text'],['manager_password','Branch Manager Password','password'],['status','Status','select',false,['active','inactive','pending']]], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Edit" onclick="komaginAdmin.openUpgradeForm('branches-list','${r.id}')"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Provision" onclick="komaginAdmin.runUpgradeAction('branches_provision_template&branch_id=${r.id}',null,'branches-list')"><i class="fas fa-server"></i></button>` },
            'branch-projects': { container:'branchProjectsContainer', list:'branch_projects_get', save:'branch_projects_save', title:'Branch Project', columns:[['branch_name','Branch'],['name','Project'],['progress_percent','Progress'],['status','Status'],['budget','Budget'],['spent','Spent'],['expected_end_date','Expected End']], fields:[['branch_id','Branch','branch_select',true],['name','Project Name','text',true],['description','Description','textarea'],['start_date','Start Date','date'],['expected_end_date','Expected End','date'],['budget','Budget','number'],['spent','Spent','number'],['progress_percent','Progress %','number'],['status','Status','select',false,['planning','active','on_hold','completed','cancelled']],['milestones','Milestones JSON','textarea']], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Edit" onclick="komaginAdmin.openUpgradeForm('branch-projects','${r.id}')"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Report" onclick="komaginAdmin.generateDocument('branch_generate_project_report&project_id=${r.id}')"><i class="fas fa-file-lines"></i></button>` },
            'branch-expenses': { container:'branchExpensesContainer', list:'branch_expenses_get', save:'branch_expenses_save', title:'Branch Expense', cardStats:true, columns:[['branch_name','Branch'],['project_name','Project'],['category','Category'],['description','Description'],['amount','Amount'],['expense_date','Date'],['status','Status']], fields:[['branch_id','Branch','branch_select',true],['project_id','Project ID','text'],['category','Category','select',false,['Materials','Labour','Equipment','Transport','Accommodation','Other']],['description','Description','textarea',true],['amount','Amount','number',true],['currency','Currency','text'],['expense_date','Expense Date','date',true],['receipt_file','Receipt File','file_upload',false,{action:'branch_expenses_upload_receipt',fieldName:'receipt',accept:'.pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp',buttonLabel:'Upload Receipt',emptyLabel:'No receipt uploaded yet.',helperText:'Upload the receipt or supporting document from your PC. Accepted: PDF, Word, Excel, JPG, PNG, WebP.',removeLabel:'Remove Receipt'}],['status','Status','select',false,['pending','approved','rejected']],['notes','Notes','textarea']], actions:(r)=>`${r.receipt_file ? `<a class="btn btn-sm btn-outline icon-btn" title="Open Receipt" href="${komaginAdmin.escapeHtml(komaginAdmin.resolveAdminAssetUrl(r.receipt_file))}" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square"></i></a>` : ''}<button class="btn btn-sm btn-success icon-btn" title="Approve" onclick="komaginAdmin.runUpgradeAction('branch_expenses_approve&id=${r.id}',null,'branch-expenses')"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Reject" onclick="komaginAdmin.runUpgradeAction('branch_expenses_reject&id=${r.id}',{notes:prompt('Rejection reason')||''},'branch-expenses')"><i class="fas fa-xmark"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Report" onclick="komaginAdmin.generateDocument('branch_generate_expense_report&branch_id=${r.branch_id}&period=${(r.expense_date||new Date().toISOString()).slice(0,7)}')"><i class="fas fa-file-lines"></i></button>` },
            'branch-assets': { container:'branchAssetsContainer', list:'branch_assets_get', title:'Branch Asset', columns:[['asset_tag','Asset Tag'],['asset_name','Asset Name'],['category','Category'],['branch_name','Branch'],['assigned_date','Assignment Date'],['condition_on_assignment','Condition'],['return_date','Return Date']], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Return" onclick="komaginAdmin.runUpgradeAction('branch_assets_return&id=${r.id}',null,'branch-assets')"><i class="fas fa-rotate-left"></i></button>` },
            'partners': { container:'partnersContainer', list:'partners_get_all', save:'partners_save_enquiry', title:'Partner', card:true, cardStats:true, columns:[['company_name','Company'],['country','Country'],['expertise','Expertise'],['enquiry_date','Enquiry Date'],['status','Status'],['nda_signed','NDA']], fields:[['company_name','Company Name','text',true],['contact_name','Contact Name','text'],['email','Email','email',true],['phone','Phone','text'],['country','Country','text'],['expertise','Expertise','textarea'],['portfolio_url','Portfolio URL','url'],['rejection_reason','Rejection Reason','textarea'],['notes','Message / Notes','textarea']], actions:(r)=>`<button class="btn btn-sm btn-outline ptnr-action-btn" type="button" title="Details" data-partner-enquiry-action="detail" data-record-id="${this.escapeHtml(String(r.id || ''))}"><i class="fas fa-address-card"></i> Details</button><button class="btn btn-sm btn-outline ptnr-action-btn" type="button" title="Edit" data-partner-enquiry-action="edit" data-record-id="${this.escapeHtml(String(r.id || ''))}"><i class="fas fa-pen-to-square"></i> Edit</button><button class="btn btn-sm btn-success ptnr-action-btn" type="button" title="Approve" data-partner-enquiry-action="approve" data-record-id="${this.escapeHtml(String(r.id || ''))}"><i class="fas fa-check"></i> Approve</button><button class="btn btn-sm btn-danger ptnr-action-btn" type="button" title="Reject" data-partner-enquiry-action="reject" data-record-id="${this.escapeHtml(String(r.id || ''))}"><i class="fas fa-xmark"></i> Reject</button><button class="btn btn-sm btn-outline ptnr-action-btn" type="button" title="NDA" data-partner-enquiry-action="nda" data-record-id="${this.escapeHtml(String(r.id || ''))}"><i class="fas fa-file-signature"></i> NDA</button><button class="btn btn-sm btn-danger ptnr-action-btn" type="button" title="Delete" data-partner-enquiry-action="delete" data-record-id="${this.escapeHtml(String(r.id || ''))}"><i class="fas fa-trash"></i> Delete</button>` },
            'partner-showcase': { container:'partnerShowcaseContainer', list:'partner_showcase_get_all', save:'partner_showcase_save', del:'partner_showcase_delete', title:'Partner Showcase Entry', card:true, cardStats:true, columns:[['company_name','Company'],['is_active','Visible'],['sort_order','Order'],['updated_at','Updated']], fields:[['company_name','Client / Partner Name','text',true],['logo','Partner Logo','image_upload'],['website_url','Website URL','url'],['partnership_purpose','Purpose of Partnership','textarea',true],['delivered_value','What Komagin Delivered','textarea',true],['sort_order','Display Order','number'],['is_active','Visible','select',false,['1','0']]], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" type="button" title="Edit" data-partner-showcase-action="edit" data-record-id="${this.escapeHtml(String(r.id || ''))}"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-danger icon-btn" type="button" title="Delete" data-partner-showcase-action="delete" data-record-id="${this.escapeHtml(String(r.id || ''))}"><i class="fas fa-trash"></i></button>` },
            'csr-items': { container:'csrItemsContainer', list:'csr_get_all', save:'csr_save', del:'csr_delete', title:'CSR Item', card:true, cardStats:true, columns:[['title','CSR Title'],['is_active','Visible'],['sort_order','Order'],['image','Image'],['updated_at','Updated']], fields:[['title','CSR Title','text',true],['description','Description','textarea'],['icon','Font Awesome Icon','text'],['image','CSR Image','image_upload'],['sort_order','Display Order','number'],['is_active','Visible','select',false,['1','0']]], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Edit" onclick="komaginAdmin.openUpgradeForm('csr-items','${r.id}')"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Delete" onclick="komaginAdmin.runUpgradeAction('csr_delete&id=${r.id}',null,'csr-items')"><i class="fas fa-trash"></i></button>` },
            'users-manage': { container:'usersManageContainer', list:'users_get_all', save:'users_save', title:'User', columns:[['username','Username'],['email','Email'],['role','Role'],['is_active','Status'],['last_login','Last Login']], fields:[['username','Username','text',true],['password','Password','password'],['email','Email','email'],['role','Role','select',false,['admin']],['is_active','Active','select',false,['1','0']]], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Edit" onclick="komaginAdmin.openUpgradeForm('users-manage','${r.id}')"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Toggle" onclick="komaginAdmin.runUpgradeAction('users_toggle_active&id=${r.id}',null,'users-manage')"><i class="fas fa-power-off"></i></button>` }
        };
    };

    KomaginAdmin.prototype.loadUpgradeSection = async function(section) {
        const cfg = this.getUpgradeConfig()[section];
        const container = document.getElementById(cfg.container);
        if (!container) return;
        this.showLoading(true);
        try {
            const period = cfg.period ? `&period=${document.getElementById('payrollPeriod')?.value || new Date().toISOString().slice(0,7)}` : '';
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=${cfg.list}${period}`),
                `${cfg.title || 'Module'} data could not be loaded`
            );
            if (!result.success) throw new Error(result.error || 'Load failed');
            this[`_${section}Data`] = result.data || [];
            container.innerHTML = this.renderUpgradeStats(result.stats) + (cfg.card ? this.renderUpgradeCards(section, result.data || []) : this.renderUpgradeTable(section, result.data || []));
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-database"></i><p>${this.escapeHtml(error.message)}</p></div>`;
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.renderUpgradeStats = function(stats) {
        if (!stats) return '';
        return `<div class="module-grid">${Object.entries(stats).map(([k,v]) => `<div class="module-card"><h3>${this.label(k)}</h3><div class="big">${this.escapeHtml(String(v ?? 0))}</div></div>`).join('')}</div>`;
    };

    KomaginAdmin.prototype.renderUpgradeTable = function(section, rows) {
        const cfg = this.getUpgradeConfig()[section];
        if (!rows.length) return `<div class="articles-list"><div class="empty-state"><i class="fas fa-inbox"></i><p>No records found</p></div></div>`;
        const th = cfg.columns.map(c => `<th>${c[1]}</th>`).join('') + '<th>Actions</th>';
        const tr = rows.map(row => `<tr>${cfg.columns.map(c => `<td>${this.renderCell(c[0], row[c[0]], row)}</td>`).join('')}<td><div class="article-actions">${cfg.actions ? cfg.actions(row) : ''}</div></td></tr>`).join('');
        return `<div class="articles-list"><div class="articles-table-container"><table class="articles-table"><thead><tr>${th}</tr></thead><tbody>${tr}</tbody></table></div></div>`;
    };

    KomaginAdmin.prototype.renderUpgradeCards = function(section, rows) {
        const cfg = this.getUpgradeConfig()[section];
        if (!rows.length) return `<div class="articles-list"><div class="empty-state"><i class="fas fa-inbox"></i><p>No records found</p></div></div>`;
        if (section === 'social-posts') {
            const spStatusBadge = (status) => {
                if (status === 'published') return 'sp-badge-pub';
                if (status === 'scheduled') return 'sp-badge-sched';
                if (status === 'failed') return 'sp-badge-fail';
                return 'sp-badge-draft';
            };
            return `<div class="sp-card-list">${rows.map(row => {
                const mediaUrl = this.resolveAdminAssetUrl(row.media_url || row.media_path || '');
                const thumb = mediaUrl && row.media_type && row.media_type !== 'none'
                    ? `<img class="sp-row-thumb" src="${this.escapeHtml(mediaUrl)}" alt="${this.escapeHtml(row.title || 'Social post')}">`
                    : `<div class="sp-row-placeholder"><i class="fas fa-photo-film"></i></div>`;
                const platforms = this.formatSocialPlatforms(row.platforms);
                const preview = this.escapeHtml((row.content || '').slice(0, 120));
                return `<div class="sp-row-card">
                    ${thumb}
                    <div class="sp-row-info">
                        <div class="sp-row-top">
                            <span class="sp-row-title">${this.escapeHtml(row.title || 'Social Post')}</span>
                            <span class="sp-badge ${spStatusBadge(row.status || 'draft')}">${this.escapeHtml(this.label(row.status || 'draft'))}</span>
                            ${row.media_type && row.media_type !== 'none' ? `<span class="sp-badge sp-badge-media"><i class="fas fa-photo-film"></i> ${this.escapeHtml(this.label(row.media_type))}</span>` : ''}
                        </div>
                        <div class="sp-row-meta">
                            <span><i class="fas fa-share-nodes"></i>${this.escapeHtml(platforms || 'No platforms selected')}</span>
                            <span><i class="fas fa-calendar"></i>${row.published_at ? this.formatDate(row.published_at) : (row.scheduled_at ? this.formatDate(row.scheduled_at) : 'Not published')}</span>
                        </div>
                        ${preview ? `<div class="sp-row-preview">${preview}</div>` : ''}
                    </div>
                    <div class="sp-row-actions">
                        <button class="btn btn-outline" onclick="komaginAdmin.openUpgradeForm('social-posts','${row.id}')"><i class="fas fa-pen-to-square"></i> Edit</button>
                        <button class="btn btn-outline" onclick="komaginAdmin.viewSocialResults('${row.id}')"><i class="fas fa-chart-column"></i> Results</button>
                        <button class="btn btn-danger" onclick="komaginAdmin.runUpgradeAction('social_delete_post&id=${row.id}',null,'social-posts')"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>`;
            }).join('')}</div>`;
        }
        if (section === 'partners') {
            const ptnrStatusBadge = (status) => {
                if (status === 'approved') return 'ptnr-badge-appr';
                if (status === 'rejected') return 'ptnr-badge-rej';
                return 'ptnr-badge-pend';
            };
            return `<div class="ptnr-card-list">${rows.map(row => {
                const initial = (row.company_name || row.contact_name || '?').charAt(0).toUpperCase();
                const ndaBadge = row.nda_signed == 1
                    ? `<span class="ptnr-badge ptnr-badge-nda"><i class="fas fa-file-signature"></i> NDA</span>`
                    : '';
                const docsBadge = Number(row.documents_count || 0) > 0
                    ? `<span><i class="fas fa-folder-open"></i>${Number(row.documents_count || 0)} doc${Number(row.documents_count || 0) === 1 ? '' : 's'}</span>`
                    : '';
                const zipBadge = row.bundle_url
                    ? `<span><i class="fas fa-file-zipper"></i>${this.escapeHtml(row.bundle_name || 'ZIP package')}</span>`
                    : '';
                const rejectionReason = String(row.rejection_reason || '').trim();
                return `<div class="ptnr-row-card">
                    <div class="ptnr-row-avatar">${this.escapeHtml(initial)}</div>
                    <div class="ptnr-row-info">
                        <div class="ptnr-row-top">
                            <span class="ptnr-row-company">${this.escapeHtml(row.company_name || 'Partner')}</span>
                            <span class="ptnr-badge ${ptnrStatusBadge(row.status || 'pending')}">${this.escapeHtml(this.label(row.status || 'pending'))}</span>
                            ${ndaBadge}
                        </div>
                        <div class="ptnr-row-meta">
                            ${row.contact_name ? `<span><i class="fas fa-user"></i>${this.escapeHtml(row.contact_name)}</span>` : ''}
                            ${row.email ? `<span><i class="fas fa-envelope"></i>${this.escapeHtml(row.email)}</span>` : ''}
                            ${row.country ? `<span><i class="fas fa-globe"></i>${this.escapeHtml(row.country)}</span>` : ''}
                            ${row.enquiry_date ? `<span><i class="fas fa-calendar"></i>${this.formatDate(row.enquiry_date)}</span>` : ''}
                            ${zipBadge}
                            ${docsBadge}
                        </div>
                        ${row.expertise ? `<div class="ptnr-row-expertise">${this.escapeHtml(row.expertise)}</div>` : ''}
                        ${row.status === 'rejected' && rejectionReason ? `<div class="ptnr-row-reason"><i class="fas fa-circle-info"></i><span>${this.escapeHtml(rejectionReason)}</span></div>` : ''}
                    </div>
                    <div class="ptnr-row-actions">
                        <button class="btn btn-outline btn-sm ptnr-action-btn" type="button" data-partner-enquiry-action="detail" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-address-card"></i> Details</button>
                        <button class="btn btn-outline btn-sm ptnr-action-btn" type="button" data-partner-enquiry-action="edit" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-pen-to-square"></i> Edit</button>
                        <button class="btn btn-success btn-sm ptnr-action-btn" type="button" data-partner-enquiry-action="approve" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-check"></i> Approve</button>
                        <button class="btn btn-danger btn-sm ptnr-action-btn" type="button" data-partner-enquiry-action="reject" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-xmark"></i> Reject</button>
                        <button class="btn btn-outline btn-sm ptnr-action-btn" type="button" data-partner-enquiry-action="nda" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-file-signature"></i> NDA</button>
                        <button class="btn btn-danger btn-sm ptnr-action-btn" type="button" data-partner-enquiry-action="delete" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>`;
            }).join('')}</div>`;
        }
        if (section === 'partner-showcase') {
            return `<div class="ptnr-showcase-list">${rows.map(row => {
                const logoUrl = row.logo ? this.resolveAdminAssetUrl(row.logo) : '';
                const purpose = String(row.partnership_purpose || '').trim();
                const delivered = String(row.delivered_value || '').trim();
                const purposePreview = purpose ? this.escapeHtml(purpose.length > 96 ? `${purpose.slice(0, 96).trim()}...` : purpose) : 'Purpose not added yet';
                const deliveredPreview = delivered ? this.escapeHtml(delivered.length > 96 ? `${delivered.slice(0, 96).trim()}...` : delivered) : 'Delivery summary not added yet';
                const visibleBadge = String(row.is_active || 0) === '1'
                    ? '<span class="ptnr-badge ptnr-badge-appr">Visible</span>'
                    : '<span class="ptnr-badge ptnr-badge-pend">Hidden</span>';
                const websiteBadge = row.website_url
                    ? `<a class="ptnr-showcase-link" href="${this.escapeHtml(row.website_url)}" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square"></i> Website</a>`
                    : '';
                return `<div class="ptnr-showcase-card">
                    <div class="ptnr-showcase-logo-wrap">
                        ${logoUrl ? `<img class="ptnr-showcase-logo" src="${this.escapeHtml(logoUrl)}" alt="${this.escapeHtml(row.company_name || 'Partner')}">` : `<div class="ptnr-showcase-logo ptnr-showcase-logo-empty"><i class="fas fa-building"></i></div>`}
                    </div>
                    <div class="ptnr-showcase-info">
                        <div class="ptnr-row-top">
                            <span class="ptnr-row-company">${this.escapeHtml(row.company_name || 'Partner')}</span>
                            ${visibleBadge}
                            ${row.sort_order !== null && row.sort_order !== undefined && row.sort_order !== '' ? `<span class="ptnr-badge ptnr-badge-nda">Order ${this.escapeHtml(String(row.sort_order))}</span>` : ''}
                        </div>
                        <div class="ptnr-row-meta">
                            ${row.website_url ? `<span><i class="fas fa-link"></i>Website linked</span>` : '<span><i class="fas fa-link-slash"></i>No website</span>'}
                            <span><i class="fas fa-image"></i>${logoUrl ? 'Logo uploaded' : 'No logo yet'}</span>
                        </div>
                        <div class="ptnr-showcase-summary">
                            <p><strong>Purpose:</strong> ${purposePreview}</p>
                            <p><strong>Delivery:</strong> ${deliveredPreview}</p>
                        </div>
                        ${websiteBadge}
                    </div>
                    <div class="ptnr-showcase-actions">
                        <button class="btn btn-outline btn-sm" type="button" data-partner-showcase-action="edit" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-pen-to-square"></i> Edit</button>
                        <button class="btn btn-danger btn-sm" type="button" data-partner-showcase-action="delete" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-trash"></i> Delete</button>
                    </div>
                </div>`;
            }).join('')}</div>`;
        }
        if (section === 'plant-hire-items') {
            const availBadge = (status) => {
                if (status === 'available') return 'hire-badge-avail';
                if (status === 'on_request') return 'hire-badge-req';
                return 'hire-badge-other';
            };
            return `<div class="hire-card-list">${rows.map(row => {
                const thumb = row.image
                    ? `<img class="hire-row-thumb" src="${this.escapeHtml(this.resolveAdminAssetUrl(row.image))}" alt="${this.escapeHtml(row.name || 'Equipment')}">`
                    : `<div class="hire-row-placeholder"><i class="fas fa-truck-monster"></i></div>`;
                const desc = this.escapeHtml((row.short_description || '').slice(0, 120));
                const isFeatured = String(row.featured || 0) === '1';
                return `<div class="hire-row-card">
                    ${thumb}
                    <div class="hire-row-info">
                        <div class="hire-row-title">${this.escapeHtml(row.name || 'Hire Equipment')}</div>
                        ${desc ? `<div class="hire-row-desc">${desc}${(row.short_description || '').length > 120 ? '...' : ''}</div>` : ''}
                        <div class="hire-row-meta">
                            <span class="hire-badge hire-badge-cat">${this.escapeHtml(this.label(row.category || 'other'))}</span>
                            <span class="hire-badge ${availBadge(row.availability_status || 'available')}">${this.escapeHtml(this.label(row.availability_status || 'available'))}</span>
                            ${isFeatured ? `<span class="hire-badge hire-badge-feat"><i class="fas fa-star"></i> Featured</span>` : ''}
                            ${row.location ? `<span class="hire-badge hire-badge-loc"><i class="fas fa-map-marker-alt"></i> ${this.escapeHtml(row.location)}</span>` : ''}
                            ${row.rate_note ? `<span class="hire-badge hire-badge-rate"><i class="fas fa-tag"></i> ${this.escapeHtml(row.rate_note)}</span>` : ''}
                        </div>
                    </div>
                    <div class="hire-row-actions">
                        ${this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: 'Edit hire item', attrs: `type="button" data-hire-action="edit" data-hire-id="${this.escapeHtml(String(row.id || ''))}"` })}
                        ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete hire item', attrs: `type="button" data-hire-action="delete" data-hire-id="${this.escapeHtml(String(row.id || ''))}"` })}
                    </div>
                </div>`;
            }).join('')}</div>`;
        }
        return `<div class="services-list">${rows.map(row => `<div class="service-card"><div class="service-header"><div class="service-icon"><i class="fas fa-briefcase"></i></div><h4>${this.escapeHtml(row.title || row.name || row.company_name || 'Record')}</h4></div><div class="service-description">${this.escapeHtml(row.description || row.expertise || row.manager_name || row.region || '')}</div><div class="service-meta">${cfg.columns.slice(1,5).map(c => `<span>${this.label(c[0])}: ${this.renderCell(c[0], row[c[0]], row)}</span>`).join('')}</div><div class="service-actions">${cfg.actions ? cfg.actions(row) : ''}</div></div>`).join('')}</div>`;
    };

    KomaginAdmin.prototype.renderSocialPostMediaPreview = function(row) {
        const mediaUrl = this.resolveAdminAssetUrl(row.media_url || row.media_path || '');
        if (!mediaUrl || !row.media_type || row.media_type === 'none') {
            return '<div class="blog-admin-thumb blog-admin-placeholder"><i class="fas fa-photo-film"></i></div>';
        }
        if (row.media_type === 'video') {
            return `<video class="blog-admin-thumb" controls preload="metadata" style="background:#0f1720;"><source src="${this.escapeHtml(mediaUrl)}"></video>`;
        }
        return `<img class="blog-admin-thumb" src="${this.escapeHtml(mediaUrl)}" alt="${this.escapeHtml(row.title || 'Social media')}">`;
    };

    KomaginAdmin.prototype.formatSocialPlatforms = function(platforms) {
        let items = platforms;
        if (typeof items === 'string') {
            try {
                items = JSON.parse(items || '[]');
            } catch (error) {
                items = String(platforms || '').split(',').map(item => item.trim()).filter(Boolean);
            }
        }
        if (!Array.isArray(items)) {
            items = String(platforms || '').split(',').map(item => item.trim()).filter(Boolean);
        }
        return items.map(item => this.label(item)).join(', ');
    };

    KomaginAdmin.prototype.renderCell = function(key, value, row) {
        if (key === 'photo') return value ? `<img src="uploads/${this.escapeHtml(value)}" style="width:42px;height:42px;object-fit:cover;border-radius:6px">` : '<i class="fas fa-user"></i>';
        if (String(key).endsWith('_file')) {
            if (!value) return '-';
            const url = this.resolveAdminAssetUrl(value);
            const fileName = String(value).split('/').pop() || 'Open file';
            return `<a href="${this.escapeHtml(url)}" target="_blank" rel="noopener">${this.escapeHtml(fileName)}</a>`;
        }
        if (key === 'image') {
            if (!value) return '-';
            const raw = String(value);
            const src = /^(https?:)?\/\//.test(raw) || raw.startsWith('/') || raw.startsWith('images/') || raw.startsWith('admin/') || raw.startsWith('adminpanel/') ? raw : `uploads/${raw}`;
            return `<img src="${this.escapeHtml(src)}" style="width:52px;height:42px;object-fit:cover;border-radius:6px">`;
        }
        if (key === 'progress_percent') {
            const n = Number(value || 0);
            const cls = n < 30 ? 'danger' : (n < 70 ? 'warn' : '');
            return `<div class="progress-bar"><div class="${cls}" style="width:${n}%"></div></div><small>${n}%</small>`;
        }
        if (String(key).includes('status') || ['condition','employment_type','type','role'].includes(key)) return `<span class="status-badge status-${String(value || 'pending').replace(/[^a-z0-9_]/gi,'_')}">${this.escapeHtml(String(value ?? '-'))}</span>`;
        if (key === 'nda_signed') return value == 1 ? '<span class="status-badge status-approved">Signed</span>' : '<span class="status-badge status-pending">Pending</span>';
        if (['amount','salary','base_salary','allowances','deductions','tax','net_pay','budget','spent','purchase_cost','current_value','cost'].includes(key)) return this.formatCurrency(value || 0);
        if (String(key).includes('date') || key === 'created_at' || key === 'last_login') return value ? this.formatDate(value) : '-';
        return this.escapeHtml(String(value ?? '-'));
    };

    KomaginAdmin.prototype.openUpgradeForm = async function(section, id = null) {
        const cfg = this.getUpgradeConfig()[section];
        if ((cfg.fields || []).some(f => f[2] === 'branch_select')) await this.ensureBranchChoices();
        if ((cfg.fields || []).some(f => f[2] === 'platform_multi')) await this.loadSocialPlatformsCache();
        const data = id ? (this[`_${section}Data`] || []).find(r => r.id === id) || {} : {};
        document.getElementById('upgradeModalTitle').textContent = `${id ? 'Edit' : 'Add'} ${cfg.title}`;
        document.getElementById('upgradeModalBody').innerHTML = `<form id="upgradeForm"><input type="hidden" name="id" value="${this.escapeHtml(data.id || '')}"><div class="form-grid">${(cfg.fields || []).map(f => this.renderField(f, data[f[0]], data)).join('')}</div><div class="form-actions"><button type="button" class="btn btn-secondary" onclick="komaginAdmin.hideModal('upgradeModal')">Cancel</button><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button></div></form>`;
        document.querySelectorAll('#upgradeForm .upgrade-image-input').forEach(input => input.addEventListener('change', () => this.uploadUpgradeImage(input)));
        document.querySelectorAll('#upgradeForm .upgrade-file-input').forEach(input => input.addEventListener('change', () => this.uploadUpgradeFile(input)));
        document.querySelectorAll('#upgradeForm .social-media-input').forEach(input => input.addEventListener('change', () => this.uploadSocialMediaFile(input)));
        document.getElementById('upgradeForm').addEventListener('submit', (event) => {
            event.preventDefault();
            this.saveUpgradeForm(section, new FormData(event.target));
        });
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.openPartnerEnquiryEditor = function(id = null) {
        const row = id ? ((this._partnersData || []).find((item) => item.id === id) || {}) : {};
        const bundleName = String(row.bundle_name || row.document_bundle_name || '').trim();
        const bundleUrl = row.bundle_url || (row.document_bundle_path ? this.resolveAdminAssetUrl(row.document_bundle_path) : '');
        document.getElementById('upgradeModalTitle').textContent = id ? 'Edit Partner Request' : 'Add Partner Request';
        document.getElementById('upgradeModalBody').innerHTML = `<form id="partnerEnquiryEditorForm" enctype="multipart/form-data">
            <input type="hidden" name="id" value="${this.escapeHtml(row.id || '')}">
            <div class="form-grid">
                <div class="form-group"><label>Company Name *</label><input type="text" name="company_name" required value="${this.escapeHtml(row.company_name || '')}" placeholder="e.g., Pacific Engineering Ltd"></div>
                <div class="form-group"><label>Contact Name</label><input type="text" name="contact_name" value="${this.escapeHtml(row.contact_name || '')}" placeholder="e.g., John Smith"></div>
                <div class="form-group"><label>Email *</label><input type="email" name="email" required value="${this.escapeHtml(row.email || '')}" placeholder="info@company.com"></div>
                <div class="form-group"><label>Phone</label><input type="text" name="phone" value="${this.escapeHtml(row.phone || '')}" placeholder="+675 1234 5678"></div>
                <div class="form-group"><label>Country</label><input type="text" name="country" value="${this.escapeHtml(row.country || '')}" placeholder="Papua New Guinea"></div>
                <div class="form-group"><label>Status</label><select name="status">
                    ${['enquiry', 'under_review', 'approved', 'active', 'rejected'].map(status => `<option value="${status}" ${String(row.status || 'enquiry') === status ? 'selected' : ''}>${this.label(status)}</option>`).join('')}
                </select></div>
                <div class="form-group" style="grid-column:1/-1"><label>Portfolio URL</label><input type="url" name="portfolio_url" value="${this.escapeHtml(row.portfolio_url || '')}" placeholder="https://company.com/portfolio"></div>
                <div class="form-group" style="grid-column:1/-1"><label>Areas of Expertise</label><textarea name="expertise" rows="4" placeholder="Describe the partner's core competencies, technical strengths, and collaboration focus.">${this.escapeHtml(row.expertise || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label>Rejection Reason</label><textarea name="rejection_reason" rows="3" placeholder="e.g., Supporting documents were incomplete for prequalification review.">${this.escapeHtml(row.rejection_reason || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label>Message / Additional Notes</label><textarea name="notes" rows="4" placeholder="Any extra context, requested scope, follow-up note, or review comment.">${this.escapeHtml(row.notes || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Supporting Documents ZIP</label>
                    <input type="file" name="partner_bundle" id="partnerBundleInput" accept=".zip,application/zip,application/x-zip-compressed,multipart/x-zip">
                    <small class="partner-bundle-note">${bundleName ? `${this.escapeHtml(bundleName)} is currently attached. Upload a new ZIP only if you want to replace it.` : 'Upload one ZIP package containing supporting company or partnership documents.'}</small>
                    <div class="file-upload-actions">
                        ${bundleUrl ? `<a class="btn btn-outline" href="${this.escapeHtml(bundleUrl)}" target="_blank" rel="noopener"><i class="fas fa-file-zipper"></i> Open Current ZIP</a>` : ''}
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.komaginAdmin && window.komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Partner</button>
            </div>
        </form>`;
        const form = document.getElementById('partnerEnquiryEditorForm');
        form?.addEventListener('submit', (event) => {
            event.preventDefault();
            this.savePartnerEnquiryForm(form);
        });
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.savePartnerEnquiryForm = async function(form) {
        if (!form) return;
        const formData = new FormData(form);
        const status = String(formData.get('status') || 'enquiry');
        const rejectionReason = String(formData.get('rejection_reason') || '').trim();
        if (status === 'rejected' && !rejectionReason) {
            this.showError('Please add a rejection reason before saving a rejected partner request.');
            return;
        }
        if (status !== 'rejected') {
            formData.set('rejection_reason', '');
        }
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=partners_save_enquiry`, {
                    method: 'POST',
                    body: formData
                }),
                'Partner request could not be saved'
            );
            if (!result.success) throw new Error(result.error || 'Partner request could not be saved');
            this.showSuccess(result.message || 'Partner request saved');
            this.hideModal('upgradeModal');
            await this.loadSectionData('partners');
            this.loadDashboardData();
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.openPartnerRejectionForm = function(id) {
        const row = ((this._partnersData || []).find((item) => item.id === id) || {});
        document.getElementById('upgradeModalTitle').textContent = 'Reject Partner Request';
        document.getElementById('upgradeModalBody').innerHTML = `<form id="partnerRejectForm">
            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1">
                    <label>Partner</label>
                    <input type="text" value="${this.escapeHtml(row.company_name || 'Partner Request')}" readonly>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Reason for Rejection *</label>
                    <textarea name="rejection_reason" rows="4" required placeholder="State clearly why this partner request is being rejected.">${this.escapeHtml(row.rejection_reason || '')}</textarea>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.komaginAdmin && window.komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                <button type="submit" class="btn btn-danger"><i class="fas fa-xmark"></i> Save Rejection</button>
            </div>
        </form>`;
        const form = document.getElementById('partnerRejectForm');
        form?.addEventListener('submit', async (event) => {
            event.preventDefault();
            const reason = String(new FormData(form).get('rejection_reason') || '').trim();
            if (!reason) {
                this.showError('A rejection reason is required.');
                return;
            }
            this.showLoading(true);
            try {
                const result = await this.readAdminJsonResponse(
                    fetch(`${this.apiBase}?action=partners_update_status&id=${encodeURIComponent(id)}`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ status: 'rejected', rejection_reason: reason })
                    }),
                    'Partner rejection could not be saved'
                );
                if (!result.success) throw new Error(result.error || 'Partner rejection could not be saved');
                this.showSuccess(result.message || 'Partner request rejected');
                this.hideModal('upgradeModal');
                await this.loadSectionData('partners');
                this.loadDashboardData();
            } catch (error) {
                this.showError(error.message);
            } finally {
                this.showLoading(false);
            }
        });
        this.showModal('upgradeModal');
    };

    KomaginAdmin.prototype.deletePartnerEnquiry = async function(id) {
        if (!window.confirm('Delete this partner request? This will also remove its uploaded ZIP and extracted files.')) {
            return;
        }
        await this.runUpgradeAction(`partners_delete&id=${encodeURIComponent(id)}`, null, 'partners');
    };

    KomaginAdmin.prototype.openPartnerEnquiryDetail = async function(id) {
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=partners_get_detail&id=${encodeURIComponent(id)}`),
                'Partner request details could not be loaded'
            );
            if (!result.success || !result.data) throw new Error(result.error || 'Partner request details could not be loaded');
            const partner = result.data;
            const statusClassMap = {
                enquiry: 'ptnr-badge-pend',
                under_review: 'ptnr-badge-pend',
                approved: 'ptnr-badge-appr',
                active: 'ptnr-badge-appr',
                rejected: 'ptnr-badge-rej'
            };
            const documents = Array.isArray(partner.documents) ? partner.documents : [];
            const groupedDocuments = documents.reduce((groups, doc) => {
                const key = doc.folder || 'Root Documents';
                if (!groups[key]) groups[key] = [];
                groups[key].push(doc);
                return groups;
            }, {});
            const documentMarkup = documents.length
                ? Object.entries(groupedDocuments).map(([folder, docs]) => `
                    <div class="application-doc-group">
                        <h4>${this.escapeHtml(folder === 'Root Documents' ? folder : folder.replace(/\//g, ' / '))}</h4>
                        <div class="application-doc-list">
                            ${docs.map(doc => `<a class="application-doc-link" href="${this.escapeHtml(doc.url || '#')}" target="_blank" rel="noopener"><i class="fas fa-file-lines"></i><span>${this.escapeHtml(doc.name || 'Document')}</span></a>`).join('')}
                        </div>
                    </div>
                `).join('')
                : '<p class="application-detail-note">No extracted partner documents are available yet.</p>';
            const initial = (partner.company_name || partner.contact_name || '?').charAt(0).toUpperCase();
            document.getElementById('upgradeModalTitle').textContent = 'Partner Request Details';
            document.getElementById('upgradeModalBody').innerHTML = `
                <div class="application-detail-layout">
                    <div class="application-detail-hero">
                        <div class="application-detail-avatar">${this.escapeHtml(initial)}</div>
                        <div class="application-detail-heading">
                            <h3>${this.escapeHtml(partner.company_name || 'Partner Request')}</h3>
                            <div class="application-detail-badges">
                                <span class="ptnr-badge ${statusClassMap[String(partner.status || 'enquiry')] || 'ptnr-badge-pend'}">${this.escapeHtml(this.label(partner.status || 'enquiry'))}</span>
                                ${partner.nda_signed == 1 ? '<span class="ptnr-badge ptnr-badge-nda"><i class="fas fa-file-signature"></i> NDA</span>' : ''}
                                ${partner.bundle_url ? '<span class="ptnr-badge ptnr-badge-nda"><i class="fas fa-file-zipper"></i> ZIP Package</span>' : ''}
                            </div>
                        </div>
                    </div>
                    <div class="application-detail-grid">
                        <div class="application-detail-card">
                            <h4>Partner Profile</h4>
                            <div class="application-detail-meta">
                                ${partner.contact_name ? `<p><strong>Contact:</strong> ${this.escapeHtml(partner.contact_name)}</p>` : ''}
                                ${partner.email ? `<p><strong>Email:</strong> ${this.escapeHtml(partner.email)}</p>` : ''}
                                ${partner.phone ? `<p><strong>Phone:</strong> ${this.escapeHtml(partner.phone)}</p>` : ''}
                                ${partner.country ? `<p><strong>Country:</strong> ${this.escapeHtml(partner.country)}</p>` : ''}
                                ${partner.enquiry_date ? `<p><strong>Submitted:</strong> ${this.formatDateTime(partner.enquiry_date)}</p>` : ''}
                            </div>
                        </div>
                        <div class="application-detail-card">
                            <div class="application-detail-card-head">
                                <h4>Bundle & Files</h4>
                                ${partner.bundle_url ? `<a class="application-doc-link" href="${this.escapeHtml(partner.bundle_url)}" target="_blank" rel="noopener"><i class="fas fa-file-zipper"></i><span>${this.escapeHtml(partner.bundle_name || 'Open ZIP')}</span></a>` : ''}
                            </div>
                            <div class="application-detail-meta">
                                <p><strong>ZIP Package:</strong> ${this.escapeHtml(partner.bundle_name || 'No ZIP uploaded')}</p>
                                <p><strong>Extracted Files:</strong> ${this.escapeHtml(String(partner.documents_count || 0))}</p>
                                ${partner.portfolio_url ? `<p><strong>Portfolio:</strong> <a href="${this.escapeHtml(partner.portfolio_url)}" target="_blank" rel="noopener">${this.escapeHtml(partner.portfolio_url)}</a></p>` : ''}
                            </div>
                        </div>
                        <div class="application-detail-card">
                            <h4>Areas of Expertise</h4>
                            <p class="application-detail-note">${this.escapeHtml(partner.expertise || 'No expertise summary provided.')}</p>
                        </div>
                        <div class="application-detail-card">
                            <h4>Notes</h4>
                            <p class="application-detail-note">${this.escapeHtml(partner.notes || 'No additional notes submitted.')}</p>
                        </div>
                        <div class="application-detail-card">
                            <h4>Rejection Reason</h4>
                            <p class="application-detail-note">${this.escapeHtml(partner.rejection_reason || 'No rejection reason saved.')}</p>
                        </div>
                        <div class="application-detail-card" style="grid-column:1/-1">
                            <h4>Extracted Partner Documents</h4>
                            ${documentMarkup}
                        </div>
                    </div>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="window.komaginAdmin && window.komaginAdmin.hideModal('upgradeModal')">Close</button>
                    </div>
                </div>
            `;
            this.showModal('upgradeModal');
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.loadPartnerShowcaseManager = async function() {
        const container = document.getElementById('partnerShowcaseContainer');
        if (!container) return;
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=partner_showcase_get_all`),
                'Partner showcase could not be loaded'
            );
            if (!result.success) throw new Error(result.error || 'Could not load partner showcase');
            this._partnerShowcaseRecords = Array.isArray(result.data) ? result.data : [];
            container.innerHTML = this.renderUpgradeStats(result.stats) + this.renderPartnerShowcaseManager(this._partnerShowcaseRecords);
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><p>${this.escapeHtml(error.message)}</p></div>`;
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.renderPartnerShowcaseManager = function(rows = []) {
        if (!rows.length) {
            return `<div class="articles-list"><div class="empty-state"><i class="fas fa-handshake"></i><p>No showcase partners yet</p></div></div>`;
        }
        return `<div class="ptnr-showcase-list">${rows.map((row) => {
            const logoUrl = row.logo ? this.resolveAdminAssetUrl(row.logo) : '';
            const purpose = String(row.partnership_purpose || '').trim();
            const purposePreview = purpose ? this.escapeHtml(purpose.length > 82 ? `${purpose.slice(0, 82).trim()}...` : purpose) : 'Purpose not added yet';
            const visibleBadge = String(row.is_active || 0) === '1'
                ? '<span class="ptnr-badge ptnr-badge-appr">Visible</span>'
                : '<span class="ptnr-badge ptnr-badge-pend">Hidden</span>';
            return `<div class="ptnr-showcase-card">
                <div class="ptnr-showcase-logo-wrap">
                    ${logoUrl ? `<img class="ptnr-showcase-logo" src="${this.escapeHtml(logoUrl)}" alt="${this.escapeHtml(row.company_name || 'Partner')}">` : `<div class="ptnr-showcase-logo ptnr-showcase-logo-empty"><i class="fas fa-building"></i></div>`}
                </div>
                <div class="ptnr-showcase-info">
                    <div class="ptnr-row-top">
                        <span class="ptnr-row-company">${this.escapeHtml(row.company_name || 'Partner')}</span>
                        ${visibleBadge}
                        ${row.sort_order !== null && row.sort_order !== undefined && row.sort_order !== '' ? `<span class="ptnr-badge ptnr-badge-nda">Order ${this.escapeHtml(String(row.sort_order))}</span>` : ''}
                    </div>
                    <div class="ptnr-showcase-meta-icons">
                        ${row.website_url ? '<span title="Website linked" aria-label="Website linked"><i class="fas fa-link"></i></span>' : '<span title="No website" aria-label="No website"><i class="fas fa-link-slash"></i></span>'}
                        ${logoUrl ? '<span title="Logo uploaded" aria-label="Logo uploaded"><i class="fas fa-image"></i></span>' : '<span title="No logo yet" aria-label="No logo yet"><i class="fas fa-image-slash"></i></span>'}
                    </div>
                    <div class="ptnr-showcase-summary">
                        <p>${purposePreview}</p>
                    </div>
                </div>
                <div class="ptnr-showcase-actions">
                    <button class="btn btn-outline btn-sm icon-btn" type="button" title="Edit" aria-label="Edit" data-partner-showcase-action="edit" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-pen-to-square"></i></button>
                    <button class="btn btn-danger btn-sm icon-btn" type="button" title="Delete" aria-label="Delete" data-partner-showcase-action="delete" data-record-id="${this.escapeHtml(String(row.id || ''))}"><i class="fas fa-trash"></i></button>
                </div>
            </div>`;
        }).join('')}</div>`;
    };

    KomaginAdmin.prototype.openPartnerShowcaseEditor = function(id = null) {
        const row = id ? ((this._partnerShowcaseRecords || []).find((item) => item.id === id) || {}) : {};
        const logoPath = String(row.logo || '').trim();
        const logoUrl = logoPath ? this.resolveAdminAssetUrl(logoPath) : '';
        document.getElementById('upgradeModalTitle').textContent = id ? 'Edit Showcase Partner' : 'Add Showcase Partner';
        document.getElementById('upgradeModalBody').innerHTML = `<form id="partnerShowcaseForm">
            <input type="hidden" name="id" value="${this.escapeHtml(row.id || '')}">
            <div class="form-grid">
                <div class="form-group"><label>Client / Partner Name *</label><input type="text" name="company_name" required value="${this.escapeHtml(row.company_name || '')}" placeholder="Client or partner name"></div>
                <div class="form-group"><label>Website URL</label><input type="url" name="website_url" value="${this.escapeHtml(row.website_url || '')}" placeholder="https://..."></div>
                <div class="form-group"><label>Display Order</label><input type="number" name="sort_order" value="${this.escapeHtml(String(row.sort_order ?? 0))}" min="0"></div>
                <div class="form-group"><label>Visibility</label><select name="is_active"><option value="1" ${String(row.is_active ?? 1) === '1' ? 'selected' : ''}>Visible</option><option value="0" ${String(row.is_active ?? 1) === '0' ? 'selected' : ''}>Hidden</option></select></div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Partner Logo</label>
                    <div class="image-upload-container">
                        <div class="image-preview ${logoUrl ? 'has-image' : ''}" id="partnerShowcaseLogoPreview">${logoUrl ? `<img src="${this.escapeHtml(logoUrl)}" alt="Partner logo" style="width:100%;max-height:180px;object-fit:contain;border-radius:8px;background:#fff;">` : '<i class="fas fa-image"></i><span>No image uploaded</span>'}</div>
                        <input type="hidden" name="logo" value="${this.escapeHtml(logoPath)}">
                        <input type="file" class="upgrade-image-input file-input" data-target="logo" accept="image/*">
                        <div class="image-upload-actions">
                            <button type="button" class="btn btn-outline" id="partnerShowcaseLogoTrigger"><i class="fas fa-upload"></i> Upload Logo</button>
                        </div>
                    </div>
                </div>
                <div class="form-group" style="grid-column:1/-1"><label>Purpose of Partnership *</label><textarea name="partnership_purpose" rows="5" required placeholder="Short explanation of why this partnership exists.">${this.escapeHtml(row.partnership_purpose || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label>What Komagin Delivered *</label><textarea name="delivered_value" rows="5" required placeholder="What Komagin delivered for this client or partner.">${this.escapeHtml(row.delivered_value || '')}</textarea></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.komaginAdmin && window.komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
            </div>
        </form>`;
        const form = document.getElementById('partnerShowcaseForm');
        const uploadInput = form.querySelector('.upgrade-image-input');
        const uploadTrigger = document.getElementById('partnerShowcaseLogoTrigger');
        if (uploadTrigger && uploadInput) uploadTrigger.addEventListener('click', () => uploadInput.click());
        if (uploadInput) uploadInput.addEventListener('change', () => this.uploadPartnerShowcaseLogo(uploadInput));
        form.addEventListener('submit', (event) => {
            event.preventDefault();
            this.savePartnerShowcaseForm(new FormData(event.target));
        });
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.uploadPartnerShowcaseLogo = async function(input) {
        const file = input.files && input.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('image', file);
        this.showLoading(true);
        try {
            const result = await fetch(`${this.apiBase}?action=upload_image`, { method:'POST', body:fd }).then((r) => r.json());
            if (!result.success) throw new Error(result.error || 'Image upload failed');
            const form = document.getElementById('partnerShowcaseForm');
            const hidden = form?.querySelector('input[name="logo"]');
            const preview = document.getElementById('partnerShowcaseLogoPreview');
            if (hidden) hidden.value = result.file_path || '';
            if (preview) {
                preview.classList.add('has-image');
                preview.innerHTML = `<img src="${this.escapeHtml(result.file_url || this.resolveAdminAssetUrl(result.file_path || ''))}" alt="Partner logo" style="width:100%;max-height:180px;object-fit:contain;border-radius:8px;background:#fff;">`;
            }
            this.showSuccess('Logo uploaded');
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.savePartnerShowcaseForm = async function(formData) {
        const data = Object.fromEntries(formData.entries());
        data.sort_order = data.sort_order === '' ? 0 : Number(data.sort_order || 0);
        data.is_active = String(data.is_active || '1');
        const saved = await this.runUpgradeAction('partner_showcase_save', data, 'partners');
        if (saved) this.hideModal('upgradeModal');
    };

    KomaginAdmin.prototype.deletePartnerShowcaseEntry = function(id) {
        return this.runUpgradeAction(`partner_showcase_delete&id=${encodeURIComponent(id)}`, null, 'partners');
    };

    KomaginAdmin.prototype.renderField = function(field, value, record = {}) {
        const [name, label, type, required, options] = field;
        const req = required ? 'required' : '';
        if (type === 'branch_select') {
            const choices = this._branchChoices || [];
            return `<div class="form-group"><label>${label}</label><select name="${name}" ${req}><option value="">Select branch</option>${choices.map(b => `<option value="${this.escapeHtml(b.id)}" ${String(value || '') === String(b.id) ? 'selected' : ''}>${this.escapeHtml(b.name || b.branch_code || b.id)}</option>`).join('')}</select></div>`;
        }
        if (type === 'platform_multi') {
            const selected = Array.isArray(value)
                ? value
                : (() => {
                    if (typeof value === 'string' && value.trim()) {
                        try {
                            const parsed = JSON.parse(value);
                            if (Array.isArray(parsed)) return parsed;
                        } catch (error) {
                            return value.split(',').map(item => item.trim()).filter(Boolean);
                        }
                    }
                    return [];
                })();
            const platforms = this._socialPlatforms || [];
            const cards = platforms.map(platform => {
                const checked = selected.includes(platform.platform) ? 'checked' : '';
                const disabled = platform.posting_ready ? '' : 'disabled';
                const statusClass = platform.posting_ready ? 'status-approved' : (platform.verification_status === 'verified' ? 'status-pending' : 'status-pending');
                return `<label class="category-card" style="padding:14px;display:flex;flex-direction:column;gap:10px;cursor:${platform.posting_ready ? 'pointer' : 'not-allowed'};opacity:${platform.posting_ready ? '1' : '0.75'};">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <input type="checkbox" name="platforms" value="${this.escapeHtml(platform.platform)}" ${checked} ${disabled}>
                        <div class="service-icon" style="width:42px;height:42px;min-width:42px;"><i class="fab ${this.escapeHtml(platform.icon || 'fa-share-alt')}"></i></div>
                        <div>
                            <strong>${this.escapeHtml(platform.display_name || this.label(platform.platform))}</strong>
                            <div><span class="status-badge ${statusClass}">${this.escapeHtml(platform.posting_ready ? 'Ready' : 'Verify first')}</span></div>
                        </div>
                    </div>
                    <small>${this.escapeHtml(platform.verification_message || 'Run verification in Social Platform Setup.')}</small>
                </label>`;
            }).join('');
            return `<div class="form-group" style="grid-column:1/-1"><label>${label}</label><div class="categories-grid">${cards || '<div class="empty-state"><p>Configure at least one social channel first.</p></div>'}</div><small>Only verified and posting-ready channels can be selected here.</small></div>`;
        }
        if (type === 'image_upload') {
            const raw = String(value || '');
            const src = /^(https?:)?\/\//.test(raw) || raw.startsWith('/') || raw.startsWith('images/') || raw.startsWith('admin/') || raw.startsWith('adminpanel/') || raw.startsWith('uploads/') ? raw : `uploads/${raw}`;
            const img = value ? `<img src="${this.escapeHtml(src)}" alt="${this.escapeHtml(label)}" style="width:100%;max-height:180px;object-fit:cover;border-radius:8px">` : '<i class="fas fa-image"></i><span>No image uploaded</span>';
            return `<div class="form-group" style="grid-column:1/-1"><label>${label}</label><div class="image-upload-container"><div class="image-preview ${value ? 'has-image' : ''}">${img}</div><input type="hidden" name="${name}" value="${this.escapeHtml(value || '')}"><input type="file" class="upgrade-image-input file-input" data-target="${name}" accept="image/*"><button type="button" class="btn btn-outline" onclick="this.previousElementSibling.click()"><i class="fas fa-upload"></i> Upload Image</button></div></div>`;
        }
        if (type === 'social_media_upload') {
            const mediaPath = String(value || '');
            const mediaType = String(record.media_type || '').trim();
            const mediaUrl = mediaPath ? this.resolveAdminAssetUrl(record.media_url || mediaPath) : '';
            const hasVideo = mediaType === 'video';
            const preview = mediaUrl
                ? (hasVideo
                    ? `<video controls preload="metadata" style="width:100%;max-height:220px;object-fit:contain;border-radius:8px;background:#0f1720;"><source src="${this.escapeHtml(mediaUrl)}"></video>`
                    : `<img src="${this.escapeHtml(mediaUrl)}" alt="${this.escapeHtml(label)}" style="width:100%;max-height:220px;object-fit:cover;border-radius:8px">`)
                : '<i class="fas fa-photo-film"></i><span>No media uploaded</span>';
            const currentLabel = mediaPath ? this.escapeHtml(mediaPath.split('/').pop()) : 'Upload an image or video from your PC for this post.';
            return `<div class="form-group" style="grid-column:1/-1">
                <label>${label}</label>
                <div class="image-upload-container social-media-upload">
                    <div class="image-preview ${mediaUrl ? 'has-image' : ''}" id="socialMediaPreview">${preview}</div>
                    <input type="hidden" name="media_path" value="${this.escapeHtml(mediaPath)}">
                    <input type="hidden" name="media_type" value="${this.escapeHtml(mediaType || (mediaPath ? 'image' : 'none'))}">
                    <input type="file" id="socialMediaInput" class="social-media-input file-input" accept="image/*,video/*">
                    <small id="socialMediaLabel">${currentLabel}</small>
                    <div class="form-actions" style="margin-top:0;padding-top:0;border-top:0;">
                        <button type="button" class="btn btn-outline" onclick="document.getElementById('socialMediaInput').click()"><i class="fas fa-upload"></i> Upload Image or Video</button>
                        <button type="button" class="btn btn-secondary" onclick="komaginAdmin.clearSocialMediaSelection()"><i class="fas fa-xmark"></i> Remove Media</button>
                    </div>
                </div>
            </div>`;
        }
        if (type === 'file_upload') {
            const uploadConfig = options && typeof options === 'object' && !Array.isArray(options) ? options : {};
            const filePath = String(value || '');
            const fileUrl = filePath ? this.resolveAdminAssetUrl(record[`${name}_url`] || filePath) : '';
            const fileName = filePath ? filePath.split('/').pop() : '';
            const helperText = this.escapeHtml(uploadConfig.helperText || 'Upload the file from your PC.');
            const buttonLabel = this.escapeHtml(uploadConfig.buttonLabel || 'Upload File');
            const emptyLabel = this.escapeHtml(uploadConfig.emptyLabel || 'No file uploaded yet.');
            const removeLabel = this.escapeHtml(uploadConfig.removeLabel || 'Remove File');
            const accept = this.escapeHtml(uploadConfig.accept || '');
            const action = this.escapeHtml(uploadConfig.action || '');
            const fieldName = this.escapeHtml(uploadConfig.fieldName || 'file');
            const preview = filePath
                ? `<div class="file-upload-card">
                        <div class="file-upload-meta">
                            <i class="fas fa-file-lines"></i>
                            <div>
                                <strong>${this.escapeHtml(fileName || 'Uploaded file')}</strong>
                                <small>Stored in admin uploads and ready to use.</small>
                            </div>
                        </div>
                        <div class="file-upload-actions">
                            <a class="btn btn-sm btn-outline icon-btn" title="Open" href="${this.escapeHtml(fileUrl)}" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square"></i></a>
                            <button type="button" class="btn btn-sm btn-secondary icon-btn" title="${this.escapeHtml(removeLabel)}" onclick="komaginAdmin.clearUpgradeFile('${this.escapeHtml(name)}')"><i class="fas fa-xmark"></i></button>
                        </div>
                    </div>`
                : `<div class="file-upload-card empty">
                        <div class="file-upload-meta">
                            <i class="fas fa-file-arrow-up"></i>
                            <div>
                                <strong>${emptyLabel}</strong>
                                <small>${helperText}</small>
                            </div>
                        </div>
                    </div>`;
            return `<div class="form-group" style="grid-column:1/-1">
                <label>${label}</label>
                <div class="image-upload-container file-upload-container" data-remove-label="${removeLabel}" data-empty-label="${emptyLabel}">
                    <div class="image-preview file-upload-preview ${filePath ? 'has-image' : ''}" id="${this.escapeHtml(name)}Preview">${preview}</div>
                    <input type="hidden" name="${name}" value="${this.escapeHtml(filePath)}">
                    <input type="file" class="upgrade-file-input file-input" data-target="${this.escapeHtml(name)}" data-action="${action}" data-field-name="${fieldName}" accept="${accept}">
                    <small class="file-upload-helper">${helperText}</small>
                    <button type="button" class="btn btn-outline" onclick="this.previousElementSibling.previousElementSibling.click()"><i class="fas fa-upload"></i> ${buttonLabel}</button>
                </div>
            </div>`;
        }
        if (type === 'textarea') return `<div class="form-group"><label>${label}</label><textarea name="${name}" ${req}>${this.escapeHtml(value || '')}</textarea></div>`;
        if (type === 'select') return `<div class="form-group"><label>${label}</label><select name="${name}" ${req}>${(options || []).map(o => `<option value="${o}" ${String(value || '') === String(o) ? 'selected' : ''}>${this.label(o)}</option>`).join('')}</select></div>`;
        const placeholder = type === 'password' ? 'Leave blank to keep existing password' : '';
        return `<div class="form-group"><label>${label}</label><input type="${type || 'text'}" name="${name}" value="${type === 'password' ? '' : this.escapeHtml(value || '')}" placeholder="${placeholder}" ${req}></div>`;
    };

    KomaginAdmin.prototype.ensureBranchChoices = async function() {
        if (this._branchChoices) return this._branchChoices;
        const result = await fetch(`${this.apiBase}?action=branches_get_all`).then(r => r.json()).catch(() => ({success:false,data:[]}));
        this._branchChoices = result.success ? (result.data || []) : [];
        return this._branchChoices;
    };

    KomaginAdmin.prototype.uploadUpgradeImage = async function(input) {
        const file = input.files && input.files[0];
        if (!file) return;
        const fd = new FormData();
        fd.append('image', file);
        this.showLoading(true);
        try {
            const result = await fetch(`${this.apiBase}?action=upload_image`, { method:'POST', body:fd }).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'Image upload failed');
            const target = input.dataset.target;
            const hidden = input.closest('.image-upload-container')?.querySelector(`input[name="${target}"]`);
            const preview = input.closest('.image-upload-container')?.querySelector('.image-preview');
            if (hidden) hidden.value = result.file_path;
            if (preview) preview.innerHTML = `<img src="${result.file_url}" alt="Uploaded image" style="width:100%;max-height:180px;object-fit:cover;border-radius:8px">`;
            this.showSuccess('Image uploaded');
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.renderUploadedFilePreview = function(name, result, helperText = 'Upload the file from your PC.', removeLabel = 'Remove File') {
        const path = String(result?.file_path || result?.path || '').trim();
        const url = this.resolveAdminAssetUrl(result?.file_url || result?.url || path);
        const fileName = path ? path.split('/').pop() : 'Uploaded file';
        return `<div class="file-upload-card">
            <div class="file-upload-meta">
                <i class="fas fa-file-lines"></i>
                <div>
                    <strong>${this.escapeHtml(fileName)}</strong>
                    <small>${this.escapeHtml(helperText)}</small>
                </div>
            </div>
            <div class="file-upload-actions">
                <a class="btn btn-sm btn-outline icon-btn" title="Open" href="${this.escapeHtml(url)}" target="_blank" rel="noopener"><i class="fas fa-up-right-from-square"></i></a>
                <button type="button" class="btn btn-sm btn-secondary icon-btn" title="${this.escapeHtml(removeLabel)}" onclick="komaginAdmin.clearUpgradeFile('${this.escapeHtml(name)}')"><i class="fas fa-xmark"></i></button>
            </div>
        </div>`;
    };

    KomaginAdmin.prototype.uploadUpgradeFile = async function(input) {
        const file = input.files && input.files[0];
        if (!file) return;
        const action = String(input.dataset.action || '').trim();
        const target = String(input.dataset.target || '').trim();
        const fieldName = String(input.dataset.fieldName || 'file').trim();
        if (!action || !target) {
            this.showError('This file upload is not configured correctly.');
            return;
        }
        const container = input.closest('.image-upload-container');
        const hidden = container?.querySelector(`input[name="${target}"]`);
        const preview = container?.querySelector('.file-upload-preview');
        const helperText = container?.querySelector('.file-upload-helper')?.textContent?.trim() || 'Upload the file from your PC.';
        const removeLabel = container?.dataset.removeLabel || 'Remove File';
        const fd = new FormData();
        fd.append(fieldName, file);
        this.showLoading(true);
        try {
            const result = await fetch(`${this.apiBase}?action=${encodeURIComponent(action)}`, { method: 'POST', body: fd }).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'File upload failed');
            if (hidden) hidden.value = result.file_path || result.path || '';
            if (preview) {
                preview.classList.add('has-image');
                preview.innerHTML = this.renderUploadedFilePreview(target, result, helperText, removeLabel);
            }
            this.showSuccess('File uploaded');
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.clearUpgradeFile = function(target) {
        const form = document.getElementById('upgradeForm');
        if (!form || !target) return;
        const hidden = form.querySelector(`input[name="${target}"]`);
        const input = form.querySelector(`.upgrade-file-input[data-target="${target}"]`);
        const preview = document.getElementById(`${target}Preview`);
        const uploadContainer = preview?.closest('.image-upload-container');
        const helper = uploadContainer?.querySelector('.file-upload-helper');
        const emptyLabel = uploadContainer?.dataset.emptyLabel || 'No file uploaded yet.';
        if (hidden) hidden.value = '';
        if (input) input.value = '';
        if (preview) {
            preview.classList.remove('has-image');
            preview.innerHTML = `<div class="file-upload-card empty">
                <div class="file-upload-meta">
                    <i class="fas fa-file-arrow-up"></i>
                    <div>
                        <strong>${this.escapeHtml(emptyLabel)}</strong>
                        <small>${this.escapeHtml(helper?.textContent?.trim() || 'Upload the file from your PC.')}</small>
                    </div>
                </div>
            </div>`;
        }
    };

    KomaginAdmin.prototype.uploadSocialMediaFile = async function(input) {
        const file = input.files && input.files[0];
        if (!file) return;
        const form = input.closest('form');
        const preview = form?.querySelector('#socialMediaPreview');
        const pathField = form?.querySelector('input[name="media_path"]');
        const typeField = form?.querySelector('input[name="media_type"]');
        const label = form?.querySelector('#socialMediaLabel');
        const fd = new FormData();
        fd.append('file', file);
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=social_upload_media`, { method: 'POST', body: fd }),
                'Social media upload failed'
            );
            if (!result.success) throw new Error(result.error || 'Media upload failed');
            if (pathField) pathField.value = result.path || '';
            if (typeField) typeField.value = result.media_type || (String(file.type || '').startsWith('video/') ? 'video' : 'image');
            if (label) label.textContent = file.name;
            if (preview) {
                preview.classList.add('has-image');
                if ((typeField?.value || '') === 'video') {
                    preview.innerHTML = `<video controls preload="metadata" style="width:100%;max-height:220px;object-fit:contain;border-radius:8px;background:#0f1720;"><source src="${this.escapeHtml(result.url)}"></video>`;
                } else {
                    preview.innerHTML = `<img src="${this.escapeHtml(result.url)}" alt="Uploaded social media" style="width:100%;max-height:220px;object-fit:cover;border-radius:8px">`;
                }
            }
            this.showSuccess('Post media uploaded');
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.applySocialMediaAsset = function(asset, form = null) {
        const targetForm = form || document.getElementById('socialPostComposeForm');
        if (!targetForm || !asset) return;
        const preview = targetForm.querySelector('#socialMediaPreview');
        const pathField = targetForm.querySelector('input[name="media_path"]');
        const typeField = targetForm.querySelector('input[name="media_type"]');
        const input = targetForm.querySelector('#socialMediaInput');
        const label = targetForm.querySelector('#socialMediaLabel');
        const filePath = String(asset.file_path || '').trim();
        const mimeType = String(asset.mime_type || '').toLowerCase();
        const mediaType = mimeType.startsWith('video/') || /\.(mp4|mov|webm|m4v|avi)$/i.test(filePath) ? 'video' : 'image';
        const displayUrl = asset.file_url || this.resolveAdminAssetUrl(filePath);
        if (pathField) pathField.value = filePath;
        if (typeField) typeField.value = filePath ? mediaType : 'none';
        if (input) input.value = '';
        if (label) label.textContent = asset.display_name || asset.original_name || asset.filename || 'Selected media';
        if (preview) {
            preview.classList.add('has-image');
            preview.innerHTML = mediaType === 'video'
                ? `<video controls preload="metadata" style="width:100%;max-height:220px;object-fit:contain;border-radius:8px;background:#0f1720;"><source src="${this.escapeHtml(displayUrl)}"></video>`
                : `<img src="${this.escapeHtml(displayUrl)}" alt="Selected social media" style="width:100%;max-height:220px;object-fit:cover;border-radius:8px">`;
        }
    };

    KomaginAdmin.prototype.openSocialMediaLibraryPicker = function(form = null) {
        const targetForm = form || document.getElementById('socialPostComposeForm');
        if (!targetForm) return;
        this.openMediaLibraryPicker({
            title: 'Choose Social Post Media',
            onConfirm: (asset) => {
                this.applySocialMediaAsset(asset, targetForm);
                this.showSuccess('Post media selected from the media library');
            }
        });
    };

    KomaginAdmin.prototype.clearSocialMediaSelection = function(form = null) {
        const targetForm = form || document.getElementById('socialPostComposeForm');
        if (!targetForm) return;
        const preview = targetForm.querySelector('#socialMediaPreview');
        const pathField = targetForm.querySelector('input[name="media_path"]');
        const typeField = targetForm.querySelector('input[name="media_type"]');
        const input = targetForm.querySelector('#socialMediaInput');
        const label = targetForm.querySelector('#socialMediaLabel');
        if (pathField) pathField.value = '';
        if (typeField) typeField.value = 'none';
        if (input) input.value = '';
        if (label) label.textContent = 'Upload an image or video from your PC for this post.';
        if (preview) {
            preview.classList.remove('has-image');
            preview.innerHTML = '<i class="fas fa-photo-film"></i><span>No media uploaded</span>';
        }
    };

    KomaginAdmin.prototype.saveUpgradeForm = async function(section, formData) {
        const cfg = this.getUpgradeConfig()[section];
        const data = Object.fromEntries(formData.entries());
        const saved = await this.runUpgradeAction(cfg.save, data, section);
        if (saved) this.hideModal('upgradeModal');
    };

    KomaginAdmin.prototype.runUpgradeAction = async function(action, data = null, reloadSection = null) {
        this.showLoading(true);
        try {
            const response = await fetch(`${this.apiBase}?action=${action}`, {
                method: data ? 'POST' : 'GET',
                headers: data ? {'Content-Type':'application/json'} : undefined,
                body: data ? JSON.stringify(data) : undefined
            });
            const result = await response.json();
            if (!result.success) throw new Error(result.error || 'Action failed');
            const creds = result.credentials || result.branch_manager;
            if (creds && creds.username && creds.password && creds.password !== 'unchanged') {
                this.showCredentialsDialog(creds.username, creds.password, result.message || 'Saved');
            } else {
                this.showSuccess(result.message || 'Done');
            }
            if (reloadSection) await this.loadSectionData(reloadSection);
            this.loadDashboardData();
            return true;
        } catch (error) {
            this.showError(error.message);
            return false;
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.showCredentialsDialog = function(username, password, message) {
        const existing = document.getElementById('credentialsDialog');
        if (existing) existing.remove();
        const dialog = document.createElement('div');
        dialog.id = 'credentialsDialog';
        dialog.style.cssText = 'position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.6);z-index:9999;display:flex;align-items:center;justify-content:center;';
        dialog.innerHTML = `<div style="background:#fff;border-radius:12px;padding:32px;max-width:420px;width:90%;box-shadow:0 8px 40px rgba(0,0,0,0.3);">
            <h3 style="margin:0 0 8px;color:#1a1a2e;font-size:18px;"><i class="fas fa-key" style="color:#4CAF50;margin-right:8px;"></i>Branch Login Credentials</h3>
            <p style="margin:0 0 20px;color:#666;font-size:13px;">${this.escapeHtml(message)}</p>
            <div style="background:#f4f7ff;border:1px solid #d0d9ff;border-radius:8px;padding:16px;margin-bottom:20px;">
                <div style="margin-bottom:10px;"><span style="font-size:12px;color:#888;display:block;margin-bottom:2px;">LOGIN ID</span><strong style="font-size:15px;color:#1a1a2e;font-family:monospace;">${this.escapeHtml(username)}</strong></div>
                <div><span style="font-size:12px;color:#888;display:block;margin-bottom:2px;">PASSWORD</span><strong style="font-size:15px;color:#1a1a2e;font-family:monospace;">${this.escapeHtml(password)}</strong></div>
            </div>
            <p style="margin:0 0 20px;color:#e53935;font-size:12px;"><i class="fas fa-exclamation-triangle"></i> Copy and save these credentials now ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â the password will not be shown again.</p>
            <button onclick="document.getElementById('credentialsDialog').remove()" style="width:100%;padding:10px;background:#4CAF50;color:#fff;border:none;border-radius:8px;font-size:14px;cursor:pointer;font-weight:600;">I have saved the credentials</button>
        </div>`;
        document.body.appendChild(dialog);
    };

    KomaginAdmin.prototype.generateDocument = async function(actionAndQuery) {
        const url = `${this.apiBase}?action=${actionAndQuery}`;
        const win = window.open('', '_blank');
        if (!win) {
            window.open(url, '_blank');
            return;
        }

        try {
            const response = await fetch(url, { credentials: 'same-origin' });
            const html = await response.text();
            if (!response.ok) {
                throw new Error('Document could not be opened');
            }

            win.document.open('text/html', 'replace');
            win.document.write(html);
            win.document.close();
            win.focus();
            win.onload = () => {
                try {
                    win.print();
                } catch (error) {
                    // Ignore print invocation issues in the child window.
                }
            };
        } catch (error) {
            win.close();
            this.showError(error.message || 'Document could not be opened');
        }
    };

    KomaginAdmin.prototype.generatePayroll = async function() {
        const period = document.getElementById('payrollPeriod')?.value || new Date().toISOString().slice(0,7);
        await this.runUpgradeAction(`hr_generate_payroll&period=${period}`, null, 'hr-payroll');
    };

    KomaginAdmin.prototype.quickAssignAsset = async function(assetId) {
        const staffId = prompt('Enter Staff ID to assign this asset to. Leave blank to cancel.');
        if (!staffId) return;
        await this.runUpgradeAction('assets_assign', { asset_id: assetId, assigned_to_staff_id: staffId, assigned_date: new Date().toISOString().slice(0,10) }, 'assets-list');
    };

    KomaginAdmin.prototype.loadFilesManager = async function(categoryId = null) {
        const container = document.getElementById('filesManagerContainer');
        if (!container) return;
        this.showLoading(true);
        try {
            const cats = await (await fetch(`${this.apiBase}?action=files_get_categories`)).json();
            if (!cats.success) throw new Error(cats.error || 'Could not load folders');
            const categories = cats.data || [];
            this._managedFolders = categories;
            const active = categoryId || categories[0]?.id || '';
            const files = active ? await (await fetch(`${this.apiBase}?action=files_get_by_category&category_id=${active}`)).json() : {success:true,data:[]};
            const activeCategory = (files && files.category) ? files.category : categories.find(item => item.id === active) || null;
            const activeFileCount = activeCategory ? Number(activeCategory.file_count ?? ((files.data || []).length || 0)) : 0;
            const isAdmin = (window.ADMIN_ROLE || 'admin') === 'admin';

            // Build folder list HTML
            const folderListHtml = categories.map(c => {
            const folderActions = isAdmin && (Number(c.can_edit || 0) === 1 || Number(c.can_delete || 0) === 1)
                    ? `<div class="fm-folder-actions">
                        ${Number(c.can_edit || 0) === 1 ? this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: `Edit ${this.escapeHtml(c.name)}`, attrs: `onclick="event.stopPropagation();komaginAdmin.openManagedFolderForm('${c.id}')"` }) : ''}
                        ${Number(c.can_delete || 0) === 1 ? this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: `Delete ${this.escapeHtml(c.name)}`, attrs: `onclick="event.stopPropagation();komaginAdmin.deleteManagedFolder('${c.id}','${this.escapeHtml(c.name)}')"` }) : ''}
                       </div>`
                    : '';
                return `<div class="fm-folder-item${c.id === active ? ' active' : ''}" onclick="komaginAdmin.loadFilesManager('${c.id}')">
                    <i class="fas fa-folder fm-folder-icon"></i>
                    <span class="fm-folder-name">${this.escapeHtml(c.name)}</span>
                    <span class="fm-folder-count">${this.escapeHtml(String(c.file_count || 0))}</span>
                    ${folderActions}
                </div>`;
            }).join('');

            // Build file grid HTML
            const fileGridHtml = active
                ? ((files.data || []).map(f => {
                    const ext = (f.original_name || f.title || '').split('.').pop().toUpperCase().slice(0, 6) || 'FILE';
                    return `<div class="fm-file-card">
                        <div class="fm-file-ext">${this.escapeHtml(ext)}</div>
                        <div class="fm-file-info">
                            <div class="fm-file-name">${this.escapeHtml(f.title || f.original_name)}</div>
                            <div class="fm-file-meta">${this.formatFileSize(f.file_size)} &bull; v${this.escapeHtml(String(f.version || 1))} &bull; ${this.escapeHtml(f.uploaded_by || '')} &bull; ${this.formatDate(f.created_at)}</div>
                        </div>
                        <div class="fm-file-actions">
                            ${this.iconButton({ action: 'link', variant: 'btn-outline', icon: 'fa-download', label: 'Download file', attrs: `href="${this.apiBase}?action=files_download&id=${f.id}" target="_blank" rel="noopener"` })}
                            ${isAdmin ? this.iconButton({ variant: 'btn-outline', icon: 'fa-bookmark', label: 'Toggle template', attrs: `onclick="komaginAdmin.runUpgradeAction('files_toggle_template&id=${f.id}',null,'files-manager')"` }) : ''}
                            ${isAdmin ? this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete file', attrs: `onclick="komaginAdmin.runUpgradeAction('files_delete&id=${f.id}',null,'files-manager')"` }) : ''}
                        </div>
                    </div>`;
                }).join('') || `<div class="empty-state"><i class="fas fa-folder-open"></i><p>No files in this folder</p></div>`)
                : '';

            container.innerHTML = `<div class="fm-layout">
                <div class="fm-sidebar">
                    <div class="fm-sidebar-header">
                        <span><i class="fas fa-folder-tree"></i> Folders</span>
                        ${isAdmin ? `<button class="btn btn-sm btn-primary icon-btn" title="New Folder" onclick="komaginAdmin.openManagedFolderForm()"><i class="fas fa-folder-plus"></i></button>` : ''}
                    </div>
                    <div class="fm-folder-list">${folderListHtml || '<div class="empty-state" style="padding:16px;"><p>No folders yet.</p></div>'}</div>
                </div>
                <div class="fm-panel">
                    ${activeCategory ? `
                    <div class="fm-panel-header">
                        <div class="fm-panel-header-info">
                            <h3><i class="fas fa-folder-open"></i> ${this.escapeHtml(activeCategory.name || 'Media Folder')}</h3>
                            <p>${this.escapeHtml(activeCategory.description || 'No folder description has been added yet.')}</p>
                            <div class="fm-panel-meta">
                                <span><i class="fas fa-shield-halved"></i>${this.escapeHtml(this.label(activeCategory.access_role || 'admin'))}</span>
                                <span><i class="fas fa-folder-open"></i>${this.escapeHtml(String(activeFileCount))} file${activeFileCount !== 1 ? 's' : ''}</span>
                                ${activeCategory.slug ? `<span><i class="fas fa-link"></i>${this.escapeHtml(activeCategory.slug)}</span>` : ''}
                            </div>
                        </div>
                        <button class="btn btn-primary" onclick="komaginAdmin.pickManagedFile('${active}')"><i class="fas fa-cloud-upload-alt"></i> Upload Files</button>
                    </div>
                    <div class="fm-file-grid">${fileGridHtml}</div>
                    ` : `<div class="empty-state"><i class="fas fa-folder-open"></i><p>Create a folder first to start uploading files.</p></div>`}
                </div>
            </div>`;
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-folder-open"></i><p>${this.escapeHtml(error.message)}</p></div>`;
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.pickManagedFile = function(categoryId) {
        const input = document.createElement('input');
        input.type = 'file';
        input.onchange = async () => {
            if (!input.files[0]) return;
            const fd = new FormData();
            fd.append('file', input.files[0]);
            fd.append('category_id', categoryId);
            fd.append('title', input.files[0].name);
            const response = await fetch(`${this.apiBase}?action=files_upload`, { method:'POST', body:fd });
            const result = await response.json();
            result.success ? this.showSuccess('File uploaded') : this.showError(result.error || 'Upload failed');
            this.loadFilesManager(categoryId);
        };
        input.click();
    };

    KomaginAdmin.prototype.openManagedFolderForm = function(id = null) {
        const folder = id ? (this._managedFolders || []).find(item => item.id === id) || {} : {};
        if (id && Number(folder.is_system_folder || 0) === 1) {
            this.showError('System media folders are protected and cannot be edited from this module.');
            return;
        }
        document.getElementById('upgradeModalTitle').textContent = id ? 'Edit Media Folder' : 'Create Media Folder';
        document.getElementById('upgradeModalBody').innerHTML = `<form id="managedFolderForm">
            <input type="hidden" name="id" value="${this.escapeHtml(folder.id || '')}">
            <div class="form-grid">
                <div class="form-group"><label>Folder Name *</label><input name="name" required placeholder="e.g. Hero Slides" value="${this.escapeHtml(folder.name || '')}"></div>
                <div class="form-group"><label>Folder Slug</label><input name="slug" placeholder="hero-slides" value="${this.escapeHtml(folder.slug || '')}"></div>
                <div class="form-group"><label>Access Role</label><select name="access_role"><option value="admin" ${String(folder.access_role || 'admin') === 'admin' ? 'selected' : ''}>Admin only</option><option value="public" ${String(folder.access_role || '') === 'public' ? 'selected' : ''}>Public assets</option><option value="hr_admin" ${String(folder.access_role || '') === 'hr_admin' ? 'selected' : ''}>HR only</option></select></div>
                <div class="form-group" style="grid-column:1/-1;"><label>Description</label><textarea name="description" rows="4" placeholder="What will this folder be used for?">${this.escapeHtml(folder.description || '')}</textarea></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas ${id ? 'fa-save' : 'fa-folder-plus'}"></i> ${id ? 'Save Folder' : 'Create Folder'}</button>
            </div>
        </form>`;
        document.getElementById('managedFolderForm').addEventListener('submit', event => {
            event.preventDefault();
            this.saveManagedFolder(new FormData(event.target));
        });
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.saveManagedFolder = async function(formData) {
        this.showLoading(true);
        try {
            const action = formData.get('id') ? 'files_update_category' : 'files_create_category';
            const result = await fetch(`${this.apiBase}?action=${action}`, { method: 'POST', body: formData }).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'Folder could not be saved');
            this.showSuccess(result.message || 'Folder saved');
            this.hideModal('upgradeModal');
            await this.loadFilesManager(result.data?.id || null);
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.deleteManagedFolder = function(id, name = 'this folder') {
        this.showStyledConfirm({
            title: 'Delete Folder',
            message: `Delete ${this.escapeHtml(name)}? This will remove the folder and any files stored inside it from the media library.`,
            confirmText: 'Delete Folder',
            confirmClass: 'btn-danger',
            onConfirm: async () => {
                this.hideModal('confirmModal');
                this.showLoading(true);
                try {
                    const result = await fetch(`${this.apiBase}?action=files_delete_category&id=${encodeURIComponent(id)}`).then(r => r.json());
                    if (!result.success) throw new Error(result.error || 'Folder could not be deleted');
                    this.showSuccess(result.message || 'Folder deleted');
                    await this.loadFilesManager();
                } catch (error) {
                    this.showError(error.message);
                } finally {
                    this.showLoading(false);
                }
            }
        });
    };

    KomaginAdmin.prototype.loadReportsHub = async function(tab = 'hr') {
        const container = document.getElementById('reportsContainer');
        const tabs = ['hr','projects','expenses','assets','partners'];
        container.innerHTML = `<div class="report-tabs">${tabs.map(t => `<button class="report-tab ${t===tab?'active':''}" onclick="komaginAdmin.loadReportsHub('${t}')">${this.label(t)}</button>`).join('')}</div><div id="reportPanel"><div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading report...</p></div></div>`;
        try {
            const result = await (await fetch(`${this.apiBase}?action=reports_get_${tab}`)).json();
            if (!result.success) throw new Error(result.error || 'Report failed');
            document.getElementById('reportPanel').innerHTML = this.renderReport(result.data || {});
        } catch (error) {
            document.getElementById('reportPanel').innerHTML = `<div class="empty-state"><p>${this.escapeHtml(error.message)}</p></div>`;
        }
    };

    KomaginAdmin.prototype.renderReport = function(data) {
        return `<div class="articles-list">${Object.entries(data).map(([key,value]) => `<h3>${this.label(key)}</h3>${Array.isArray(value) ? this.renderReportRows(value) : this.renderReportObject(value)}</div>`).join('<hr>')}</div>`;
    };

    KomaginAdmin.prototype.renderReportRows = function(rows) {
        if (!rows.length) return '<p>No data</p>';
        const max = Math.max(...rows.map(r => Number(r.value || r.count || 0)), 1);
        return rows.map(r => `<div class="bar-row"><strong>${this.escapeHtml(r.label || r.name || r.status || r.company_name || 'Item')}</strong><div class="bar-track"><span style="width:${Math.round((Number(r.value || r.count || 0) / max) * 100)}%"></span></div><span>${this.escapeHtml(String(r.value || r.count || ''))}</span></div>`).join('');
    };

    KomaginAdmin.prototype.renderReportObject = function(obj) {
        if (!obj) return '<p>No data</p>';
        return `<div class="module-grid">${Object.entries(obj).map(([k,v]) => `<div class="module-card"><h3>${this.label(k)}</h3><div class="big">${this.escapeHtml(String(v ?? 0))}</div></div>`).join('')}</div>`;
    };

    KomaginAdmin.prototype.loadPendingApprovals = async function() {
        const target = document.getElementById('pendingApprovalsList');
        if (!target) return;
        target.innerHTML = '<div class="empty-state"><i class="fas fa-hourglass-half"></i><p>Use the HR, expense, and partner sections to review pending items.</p></div>';
    };

    KomaginAdmin.prototype.loadBranchHub = async function(tab = 'content') {
        const container = document.getElementById('branchHubContainer');
        if (!container) return;
        this.showLoading(true);
        try {
            const result = await (await fetch(`${this.apiBase}?action=branch_hub_get`)).json();
            if (!result.success) throw new Error(result.error || 'Could not load branch hub');
            this._branchHubData = result.data || {};
            const tabs = ['content','template_submissions','site_reports','expenses','rfis','milestones'];
            container.innerHTML = `
                ${this.renderUpgradeStats(this._branchHubData.stats || {})}
                <div class="report-tabs">${tabs.map(t => `<button class="report-tab ${t===tab?'active':''}" onclick="komaginAdmin.renderBranchHubTab('${t}')">${this.label(t)}</button>`).join('')}</div>
                <div id="branchHubPanel"></div>
            `;
            this.renderBranchHubTab(tab);
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-sitemap"></i><p>${this.escapeHtml(error.message)}</p></div>`;
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.renderBranchHubTab = function(tab) {
        const panel = document.getElementById('branchHubPanel');
        if (!panel) return;
        document.querySelectorAll('#branchHubContainer .report-tab').forEach(btn => btn.classList.toggle('active', btn.textContent.trim() === this.label(tab)));
        const rows = (this._branchHubData && this._branchHubData[tab]) || [];
        const configs = {
            content: {
                cols: [['branch_name','Branch'],['title','Title'],['submission_type','Type'],['status','Status'],['submitted_by','Submitted By'],['created_at','Submitted']],
                actions: r => `${r.file_path ? `<a class="btn btn-sm btn-outline icon-btn" title="Open" href="uploads/${this.escapeHtml(r.file_path)}" target="_blank"><i class="fas fa-up-right-from-square"></i></a>` : ''}<button class="btn btn-sm btn-success icon-btn" title="Approve" onclick="komaginAdmin.runUpgradeAction('branch_content_set_status&id=${r.id}&status=approved',null,'branch-hub')"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Review" onclick="komaginAdmin.runUpgradeAction('branch_content_set_status&id=${r.id}&status=under_review',null,'branch-hub')"><i class="fas fa-eye"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Reject" onclick="komaginAdmin.rejectBranchContent('${r.id}')"><i class="fas fa-xmark"></i></button>`
            },
            template_submissions: {
                cols: [['branch_name','Branch'],['title','Title'],['template_title','Template'],['save_directory','Directory'],['status','Status'],['updated_at','Updated']],
                actions: r => `<button class="btn btn-sm btn-outline icon-btn" title="View" onclick="komaginAdmin.viewTemplateSubmission('${r.id}')"><i class="fas fa-eye"></i></button>`
            },
            site_reports: {
                cols: [['branch_name','Branch'],['project_name','Project'],['report_date','Date'],['report_type','Type'],['status','Status'],['submitted_by','Submitted By']],
                actions: r => `<button class="btn btn-sm btn-outline icon-btn" title="View" onclick="komaginAdmin.viewBranchReport('${r.id}')"><i class="fas fa-eye"></i></button><button class="btn btn-sm btn-success icon-btn" title="Verify" onclick="komaginAdmin.runUpgradeAction('branch_reports_verify&id=${r.id}',null,'branch-hub')"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Flag" onclick="komaginAdmin.runUpgradeAction('branch_reports_flag&id=${r.id}',null,'branch-hub')"><i class="fas fa-flag"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Print" onclick="komaginAdmin.generateDocument('branch_generate_site_report&id=${r.id}')"><i class="fas fa-print"></i></button>`
            },
            expenses: {
                cols: [['branch_name','Branch'],['project_name','Project'],['category','Category'],['amount','Amount'],['expense_date','Date'],['status','Status']],
                actions: r => `<button class="btn btn-sm btn-success icon-btn" title="Approve" onclick="komaginAdmin.runUpgradeAction('branch_expenses_approve&id=${r.id}',null,'branch-hub')"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Reject" onclick="komaginAdmin.runUpgradeAction('branch_expenses_reject&id=${r.id}',{notes:prompt('Rejection reason')||''},'branch-hub')"><i class="fas fa-xmark"></i></button>`
            },
            rfis: {
                cols: [['rfi_number','RFI'],['branch_name','Branch'],['project_name','Project'],['subject','Subject'],['priority','Priority'],['status','Status']],
                actions: r => `<button class="btn btn-sm btn-outline icon-btn" title="Answer" onclick="komaginAdmin.answerRfi('${r.id}')"><i class="fas fa-reply"></i></button><button class="btn btn-sm btn-success icon-btn" title="Close" onclick="komaginAdmin.runUpgradeAction('branch_rfis_close&id=${r.id}',null,'branch-hub')"><i class="fas fa-check"></i></button>`
            },
            milestones: {
                cols: [['branch_name','Branch'],['project_name','Project'],['title','Milestone'],['due_date','Due'],['progress_percent','Progress'],['status','Status']],
                actions: r => `<button class="btn btn-sm btn-success icon-btn" title="Complete" onclick="komaginAdmin.runUpgradeAction('branch_milestones_complete&id=${r.id}',null,'branch-hub')"><i class="fas fa-check"></i></button>`
            }
        };
        const cfg = configs[tab] || configs.content;
        if (!rows.length) {
            panel.innerHTML = `<div class="articles-list"><div class="empty-state"><i class="fas fa-inbox"></i><p>No ${this.label(tab).toLowerCase()} records found.</p></div></div>`;
            return;
        }
        const head = cfg.cols.map(c => `<th>${c[1]}</th>`).join('') + '<th>Actions</th>';
        const body = rows.map(row => `<tr>${cfg.cols.map(c => `<td data-label="${c[1]}">${this.renderCell(c[0], row[c[0]], row)}</td>`).join('')}<td data-label="Actions"><div class="article-actions">${cfg.actions(row)}</div></td></tr>`).join('');
        panel.innerHTML = `<div class="articles-list"><div class="articles-table-container"><table class="articles-table"><thead><tr>${head}</tr></thead><tbody>${body}</tbody></table></div></div>`;
    };

    KomaginAdmin.prototype.rejectBranchContent = function(id) {
        const notes = prompt('Rejection reason');
        this.runUpgradeAction(`branch_content_set_status&id=${id}&status=rejected`, { admin_notes: notes || '' }, 'branch-hub');
    };

    KomaginAdmin.prototype.viewBranchReport = function(id) {
        const pools = [
            ...(this._branchHubData?.site_reports || []),
            ...(this['_branch-site-reportsData'] || [])
        ];
        const r = pools.find(row => row.id === id);
        if (!r) return this.showError('Report details are not loaded yet');
        const attachmentUrl = r.attachment_path ? `uploads/${this.escapeHtml(r.attachment_path)}` : '';
        const isPdf = /pdf/i.test(r.attachment_mime || r.attachment_name || '');
        const isImage = /image/i.test(r.attachment_mime || '') || /\.(jpg|jpeg|png|webp|gif)$/i.test(r.attachment_name || r.attachment_path || '');
        const attachment = attachmentUrl ? `
            <div class="report-attachment">
                <h4><i class="fas fa-paperclip"></i> Attachment</h4>
                <p><a class="btn btn-sm btn-outline icon-btn" title="${this.escapeHtml(r.attachment_name || 'Open attachment')}" href="${attachmentUrl}" target="_blank"><i class="fas fa-paperclip"></i></a></p>
                ${isPdf ? `<iframe src="${attachmentUrl}" style="width:100%;height:520px;border:1px solid #dfe3ea;border-radius:8px"></iframe>` : ''}
                ${isImage ? `<img src="${attachmentUrl}" alt="${this.escapeHtml(r.attachment_name || 'Report attachment')}" style="width:100%;max-height:520px;object-fit:contain;border:1px solid #dfe3ea;border-radius:8px;background:#f8fafc">` : ''}
            </div>` : '<div class="empty-state"><i class="fas fa-paperclip"></i><p>No attachment uploaded with this report.</p></div>';
        document.getElementById('upgradeModalTitle').textContent = `Site Report - ${this.escapeHtml(r.branch_name || 'Branch')}`;
        document.getElementById('upgradeModalBody').innerHTML = `
            <div class="professional-report">
                <div class="module-grid">
                    <div class="module-card"><h3>Branch</h3><div class="big">${this.escapeHtml(r.branch_name || '-')}</div></div>
                    <div class="module-card"><h3>Project</h3><div class="big">${this.escapeHtml(r.project_name || 'General')}</div></div>
                    <div class="module-card"><h3>Date</h3><div class="big">${this.escapeHtml(r.report_date || '-')}</div></div>
                    <div class="module-card"><h3>Status</h3><div class="big">${this.escapeHtml(this.label(r.status || 'submitted'))}</div></div>
                </div>
                <table class="articles-table" style="margin:18px 0">
                    <tbody>
                        ${[['Type',r.report_type],['Weather',r.weather],['Workers',r.workers_on_site],['Safety Incidents',r.safety_incidents],['Submitted By',r.submitted_by],['Activities Done',r.activities_done],['Issues Raised',r.issues_raised],['Materials Used',r.materials_used],['Equipment Used',r.equipment_used],['Incident Detail',r.incident_detail]].map(([k,v]) => `<tr><th>${k}</th><td>${String(v || '-').includes('\n') ? this.escapeHtml(String(v || '-')).replace(/\n/g,'<br>') : this.escapeHtml(String(v || '-'))}</td></tr>`).join('')}
                    </tbody>
                </table>
                ${attachment}
                <div class="form-actions"><button class="btn btn-primary" onclick="komaginAdmin.generateDocument('branch_generate_site_report&id=${r.id}')"><i class="fas fa-print"></i> Print Professional Report</button></div>
            </div>`;
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.viewTemplateSubmission = function(id) {
        const r = (this._branchHubData?.template_submissions || []).find(row => row.id === id);
        if (!r) return this.showError('Template submission not loaded yet');
        let formData = {};
        try { formData = JSON.parse(r.form_data || '{}'); } catch (e) {}
        document.getElementById('upgradeModalTitle').textContent = `Template Submission - ${r.branch_name || 'Branch'}`;
        document.getElementById('upgradeModalBody').innerHTML = `
            <div class="professional-report">
                <div class="module-grid">
                    <div class="module-card"><h3>Branch</h3><div class="big">${this.escapeHtml(r.branch_name || '-')}</div></div>
                    <div class="module-card"><h3>Template</h3><div class="big">${this.escapeHtml(r.template_title || '-')}</div></div>
                    <div class="module-card"><h3>Status</h3><div class="big">${this.escapeHtml(this.label(r.status || 'draft'))}</div></div>
                    <div class="module-card"><h3>Directory</h3><div class="big">${this.escapeHtml(r.save_directory || 'Branch Templates')}</div></div>
                </div>
                <table class="articles-table" style="margin-top:18px"><tbody>${Object.entries(formData).map(([k,v]) => `<tr><th>${this.label(k)}</th><td>${this.escapeHtml(String(v || '-')).replace(/\n/g,'<br>')}</td></tr>`).join('') || '<tr><td>No filled fields.</td></tr>'}</tbody></table>
            </div>`;
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.formatCurrency = function(amount) {
        return `PGK ${Number(amount || 0).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`;
    };

    KomaginAdmin.prototype.formatFileSize = function(bytes) {
        const n = Number(bytes || 0);
        if (n < 1024) return `${n} B`;
        if (n < 1024 * 1024) return `${(n/1024).toFixed(1)} KB`;
        return `${(n/(1024*1024)).toFixed(1)} MB`;
    };

    KomaginAdmin.prototype.label = function(value) {
        return String(value || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    };
})();

// DASHBOARD GRAPH RESCUE: render critical dashboard graphs from direct endpoints, independent of earlier dashboard overrides.
(function() {
    if (window.__komaginDashboardGraphRescue) return;
    window.__komaginDashboardGraphRescue = true;

    const palette = ['#1A3A5C', '#E8A317', '#27AE60', '#3498DB', '#E67E22', '#8E44AD', '#16A085', '#E74C3C'];

    const labelize = (value) => String(value || '')
        .replace(/_/g, ' ')
        .replace(/\b\w/g, c => c.toUpperCase());

    const escapeHtml = (value) => String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#39;');

    async function safeAdminJson(url, fallbackMessage = 'Dashboard data could not be loaded') {
        const response = await fetch(url, { credentials: 'same-origin' });
        const payload = await response.text();
        let result = null;
        try {
            result = JSON.parse(payload);
        } catch (error) {
            const normalized = String(payload || '').trim();
            if (!response.ok || normalized.startsWith('<') || /Authentication required|login/i.test(normalized)) {
                throw new Error('Admin session expired. Please log in again.');
            }
            throw new Error(fallbackMessage);
        }
        if (!response.ok || !result?.success) throw new Error(result?.error || fallbackMessage);
        return result;
    }

    function getRecentMonthBuckets(count = 6) {
        const now = new Date();
        const buckets = [];
        for (let offset = count - 1; offset >= 0; offset--) {
            const date = new Date(now.getFullYear(), now.getMonth() - offset, 1);
            buckets.push({
                key: `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`,
                label: date.toLocaleString('en-US', { month: 'short' })
            });
        }
        return buckets;
    }

    function bucketRowsByMonth(rows, dateKey, buckets) {
        return buckets.map(bucket => rows.filter(row => String(row?.[dateKey] || '').slice(0, 7) === bucket.key).length);
    }

    function renderEmpty(targetId, message) {
        const target = document.getElementById(targetId);
        if (!target) return;
        target.innerHTML = `<div class="chart-empty">${escapeHtml(message)}</div>`;
    }

    function renderDonut(targetId, items, centerLabel = '') {
        const target = document.getElementById(targetId);
        if (!target) return;
        const rows = (items || []).filter(item => Number(item?.value || 0) > 0);
        if (!rows.length) {
            target.innerHTML = `<div class="chart-empty">No dashboard data available yet for this chart.</div>`;
            return;
        }
        const total = rows.reduce((sum, item) => sum + Number(item.value || 0), 0) || 1;
        const radius = 78;
        const circumference = 2 * Math.PI * radius;
        let offset = 0;
        const arcs = rows.map(item => {
            const value = Number(item.value || 0);
            const length = (value / total) * circumference;
            const dashOffset = circumference - offset;
            offset += length;
            return `<circle cx="110" cy="110" r="${radius}" fill="none" stroke="${item.color}" stroke-width="22" stroke-linecap="round" stroke-dasharray="${length} ${circumference - length}" stroke-dashoffset="${dashOffset}" transform="rotate(-90 110 110)"></circle>`;
        }).join('');
        const legend = rows.map(item => {
            const pct = Math.round((Number(item.value || 0) / total) * 100);
            return `<div class="chart-legend-item"><span class="chart-legend-label"><span class="chart-swatch" style="background:${item.color}"></span>${escapeHtml(item.label)}</span><strong>${escapeHtml(String(item.value || 0))} <span class="chart-legend-pct">${pct}%</span></strong></div>`;
        }).join('');
        target.innerHTML = `
            <div class="chart-scroll">
                <div class="chart-donut-layout chart-scroll-inner-medium">
                    <svg class="chart-svg donut-svg" viewBox="0 0 220 220" aria-hidden="true">
                        <circle cx="110" cy="110" r="${radius}" fill="none" stroke="#e9edf2" stroke-width="22"></circle>
                        ${arcs}
                        <text x="110" y="104" text-anchor="middle" font-size="14" fill="#667085">${escapeHtml(centerLabel)}</text>
                        <text x="110" y="126" text-anchor="middle" font-size="26" font-weight="700" fill="#1A3A5C">${escapeHtml(String(total))}</text>
                    </svg>
                    <div class="chart-legend">${legend}</div>
                </div>
            </div>
        `;
    }

    function renderLine(targetId, labels, seriesList) {
        const target = document.getElementById(targetId);
        if (!target) return;
        const rows = (seriesList || []).filter(series => Array.isArray(series?.values) && series.values.length);
        const max = Math.max(1, ...rows.flatMap(series => series.values.map(value => Number(value || 0))));
        if (!rows.length || max <= 0) {
            target.innerHTML = `<div class="chart-empty">No trend data is available yet for this chart.</div>`;
            return;
        }
        const width = 760;
        const height = 280;
        const padX = 50;
        const padY = 26;
        const chartWidth = width - padX * 2;
        const chartHeight = height - padY * 2 - 28;
        const xStep = labels.length > 1 ? chartWidth / (labels.length - 1) : chartWidth;
        const yFor = (value) => padY + chartHeight - ((Number(value || 0) / max) * chartHeight);
        const grid = Array.from({ length: 5 }, (_, index) => {
            const value = Math.round((max / 4) * index);
            const y = yFor(value);
            return `<line x1="${padX}" y1="${y}" x2="${width - padX}" y2="${y}" stroke="#e6ebf2" stroke-width="1"></line>
                    <text x="${padX - 10}" y="${y + 4}" text-anchor="end" font-size="11" fill="#8b98a9">${value}</text>`;
        }).join('');
        const seriesSvg = rows.map(series => {
            const points = series.values.map((value, index) => `${padX + (xStep * index)},${yFor(value)}`).join(' ');
            const dots = series.values.map((value, index) => `<circle cx="${padX + (xStep * index)}" cy="${yFor(value)}" r="4.5" fill="${series.color}" stroke="#fff" stroke-width="2"></circle>`).join('');
            return `<polyline fill="none" stroke="${series.color}" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round" points="${points}"></polyline>${dots}`;
        }).join('');
        const xLabels = labels.map((label, index) => `<text x="${padX + (xStep * index)}" y="${height - 12}" text-anchor="middle" font-size="11" fill="#8b98a9">${escapeHtml(label)}</text>`).join('');
        const legend = rows.map(series => `<span class="line-chart-pill"><span class="chart-swatch" style="background:${series.color}"></span>${escapeHtml(series.label)}</span>`).join('');
        target.innerHTML = `
            <div class="chart-scroll">
                <div class="chart-scroll-inner-wide">
                    <svg class="chart-svg" viewBox="0 0 ${width} ${height}" aria-hidden="true">
                        ${grid}
                        <line x1="${padX}" y1="${padY + chartHeight}" x2="${width - padX}" y2="${padY + chartHeight}" stroke="#cfd8e3" stroke-width="1.5"></line>
                        ${seriesSvg}
                        ${xLabels}
                    </svg>
                    <div class="line-chart-meta">${legend}</div>
                </div>
            </div>
        `;
    }

    function renderBars(targetId, items) {
        const target = document.getElementById(targetId);
        if (!target) return;
        const rows = items.filter(item => Number(item.value || 0) >= 0);
        const max = Math.max(...rows.map(item => Number(item.value || 0)), 0);
        if (!max) {
            target.innerHTML = `<div class="chart-empty">No communications data yet.</div>`;
            return;
        }
        const bars = rows.map((item, i) => {
            const value = Number(item.value || 0);
            const height = Math.max(10, Math.round((value / max) * 160));
            return `<div class="chart-bar-group">
                <div class="chart-bar-value">${escapeHtml(String(value))}</div>
                <div class="chart-bar-stack"><div class="chart-bar" style="height:${height}px;background:${escapeHtml(item.color)};border-radius:4px 4px 0 0;"></div></div>
                <div class="chart-bar-label">${escapeHtml(item.label)}</div>
            </div>`;
        }).join('');
        target.innerHTML = `<div class="chart-scroll"><div class="chart-scroll-inner-wide"><div class="chart-bars">${bars}</div><div class="line-chart-meta"><span class="line-chart-pill">Peak: ${escapeHtml(String(max))}</span></div></div></div>`;
    }

    async function rescueDashboardGraphs() {
        const dashboard = document.getElementById('dashboard');
        if (!dashboard) return;
        const chartGrid = dashboard.querySelector('.dashboard-charts');
        if (chartGrid) chartGrid.style.display = 'grid';

        try {
            const [
                projectsResult,
                servicesResult,
                testimonialsResult,
                teamResult,
                contactsResult,
                subscribersResult,
                socialPlatformsResult,
                hireItemsResult
            ] = await Promise.all([
                safeAdminJson('admin.php?action=get_projects', 'Projects could not be loaded for the dashboard'),
                safeAdminJson('admin.php?action=get_services', 'Services could not be loaded for the dashboard'),
                safeAdminJson('admin.php?action=get_testimonials', 'Testimonials could not be loaded for the dashboard'),
                safeAdminJson('admin.php?action=get_team', 'Team records could not be loaded for the dashboard'),
                safeAdminJson('admin.php?action=get_contacts', 'Contacts could not be loaded for the dashboard'),
                safeAdminJson('admin.php?action=get_subscribers', 'Subscribers could not be loaded for the dashboard'),
                safeAdminJson('admin.php?action=social_get_platforms', 'Social platforms could not be loaded for the dashboard'),
                safeAdminJson('admin.php?action=hire_items_get_all', 'Hire items could not be loaded for the dashboard')
            ]);

            const projects = projectsResult.data || [];
            const services = servicesResult.data || [];
            const testimonials = testimonialsResult.data || [];
            const team = teamResult.data || [];
            const contacts = contactsResult.data || [];
            const subscribers = subscribersResult.data || [];
            const platforms = socialPlatformsResult.data || [];
            const hireItems = hireItemsResult.data || [];

            const contentPortfolioData = [
                { label: 'Projects', value: projects.length, color: palette[0] },
                { label: 'Services', value: services.length, color: palette[1] },
                { label: 'Testimonials', value: testimonials.length, color: palette[2] },
                { label: 'Team', value: team.length, color: palette[3] }
            ];

            const projectCategoryCounts = projects.reduce((acc, project) => {
                const key = String(project.category || 'uncategorized').trim() || 'uncategorized';
                acc[key] = (acc[key] || 0) + 1;
                return acc;
            }, {});
            const projectCategoryData = Object.entries(projectCategoryCounts)
                .sort((a, b) => b[1] - a[1])
                .map(([key, value], index) => ({
                    label: labelize(key),
                    value,
                    color: palette[index % palette.length]
                }));

            const buckets = getRecentMonthBuckets(6);
            const contactSeries = bucketRowsByMonth(contacts, 'created_at', buckets);
            const subscriberSeries = bucketRowsByMonth(subscribers, 'subscribed_at', buckets);

            const readyChannels = platforms.filter(item => Number(item.posting_ready || 0) === 1).length;
            const verifiedOnly = platforms.filter(item => Number(item.posting_ready || 0) !== 1 && String(item.verification_status || '') === 'verified').length;
            const setupNeeded = Math.max(platforms.length - readyChannels - verifiedOnly, 0);
            const channelReadinessData = [
                { label: 'Ready to Post', value: readyChannels, color: palette[2] },
                { label: 'Verified Only', value: verifiedOnly, color: palette[1] },
                { label: 'Needs Setup', value: setupNeeded, color: palette[7] }
            ];

            const volumeData = [
                { label: 'Contact Messages', value: contacts.length, color: palette[0] },
                { label: 'Email Subscriptions', value: subscribers.length, color: palette[1] },
                { label: 'Equipment Listed', value: hireItems.length, color: palette[2] }
            ];

            renderDonut('dashboardContentMixChart', contentPortfolioData, 'Managed Content');
            renderDonut('dashboardProjectCategoryChart', projectCategoryData, 'Projects');
            renderLine('dashboardTrendChart', buckets.map(item => item.label), [
                { label: 'Contacts', values: contactSeries, color: palette[0] },
                { label: 'Subscribers', values: subscriberSeries, color: palette[1] }
            ]);
            renderDonut('dashboardChannelReadinessChart', channelReadinessData, 'Channels');
            renderBars('dashboardVolumeChart', volumeData);
        } catch (error) {
            const message = error?.message || 'Dashboard graphs could not be loaded';
            renderEmpty('dashboardContentMixChart', message);
            renderEmpty('dashboardProjectCategoryChart', message);
            renderEmpty('dashboardTrendChart', message);
            renderEmpty('dashboardChannelReadinessChart', message);
            renderEmpty('dashboardVolumeChart', message);
        }
    }

    function startRescue() {
        rescueDashboardGraphs();
        const refreshBtn = document.getElementById('refreshDashboard');
        if (refreshBtn && !refreshBtn.dataset.graphRescueBound) {
            refreshBtn.dataset.graphRescueBound = '1';
            refreshBtn.addEventListener('click', () => {
                setTimeout(rescueDashboardGraphs, 50);
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startRescue);
    } else {
        startRescue();
    }
    window.addEventListener('load', () => {
        setTimeout(rescueDashboardGraphs, 120);
    });
})();

// DASHBOARD FINALIZATION PASS - LAST OVERRIDE: keep the dashboard tied to live module sources and the final requested layout.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__dashboardFinalizationLastPass) return;
    KomaginAdmin.prototype.__dashboardFinalizationLastPass = true;

    const dashboardPalette = ['#1A3A5C', '#E8A317', '#27AE60', '#3498DB', '#E67E22', '#8E44AD', '#16A085', '#E74C3C'];

    KomaginAdmin.prototype.loadDashboardData = async function() {
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=get_stats`),
                'Dashboard data could not be loaded'
            );
            if (result.success) {
                const stats = result.data || {};
                this.setText('totalProjects', stats.total_projects || 0);
                this.setText('totalServices', stats.total_services || 0);
                this.setText('totalTestimonials', stats.total_testimonials || 0);
                this.setText('totalTeam', stats.total_team || 0);
                this.setText('totalContacts', stats.total_contacts || 0);
                this.setText('totalSubscribers', stats.total_subscribers || 0);
                this.setText('totalSubscribersStat', stats.total_subscribers || 0);
                this.setText('openJobs', stats.open_jobs || 0);
                this.setText('newApplications', stats.new_applications || 0);
                this.setText('staffCount', stats.staff_count || 0);
                this.setText('openAssets', stats.open_assets || 0);
                this.setText('pendingApprovals', stats.pending_approvals || 0);
                this.setText('activeBranches', stats.active_branches || 0);
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }

        await this.refreshDashboardDataFeeds();

        const subscribers = Array.isArray(this.dashboardSubscribers) ? this.dashboardSubscribers : [];
        const activeSubscribers = subscribers.filter(item => String(item.status || 'active') === 'active').length;
        const monthKey = `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}`;
        const newThisMonth = subscribers.filter(item => String(item.subscribed_at || '').slice(0, 7) === monthKey).length;
        this.setText('activeSubscribers', activeSubscribers);
        this.setText('newSubscribersMonth', newThisMonth);

        this.renderDashboardCharts();
    };

    KomaginAdmin.prototype.refreshDashboardDataFeeds = async function() {
        const safeRead = async (url, fallbackMessage = 'Dashboard data could not be loaded') => {
            try {
                return await this.readAdminJsonResponse(fetch(url), fallbackMessage);
            } catch (error) {
                return { success: false, data: [] };
            }
        };

        const [
            projectsResult,
            servicesResult,
            testimonialsResult,
            teamResult,
            contactsResult,
            subscribersResult,
            jobsResult,
            documentsResult,
            hireResult,
            applicationsResult
        ] = await Promise.all([
            safeRead(`${this.apiBase}?action=get_projects`),
            safeRead(`${this.apiBase}?action=get_services`),
            safeRead(`${this.apiBase}?action=get_testimonials`),
            safeRead(`${this.apiBase}?action=get_team`),
            safeRead(`${this.apiBase}?action=get_contacts`),
            safeRead(`${this.apiBase}?action=get_subscribers`),
            safeRead(`${this.apiBase}?action=hr_get_jobs`),
            safeRead(`${this.apiBase}?action=documents_get_all`),
            safeRead(`${this.apiBase}?action=hire_items_get_all`),
            safeRead(`${this.apiBase}?action=hr_get_applications&status=all`)
        ]);

        this.dashboardProjects = projectsResult.success ? (projectsResult.data || []) : [];
        this.dashboardServices = servicesResult.success ? (servicesResult.data || []) : [];
        this.dashboardTestimonials = testimonialsResult.success ? (testimonialsResult.data || []) : [];
        this.dashboardTeam = teamResult.success ? (teamResult.data || []) : [];
        this.dashboardContacts = contactsResult.success ? (contactsResult.data || []) : [];
        this.dashboardSubscribers = subscribersResult.success ? (subscribersResult.data || []) : [];
        this.dashboardJobs = jobsResult.success ? (jobsResult.data || []) : [];
        this.dashboardDocuments = documentsResult.success ? (documentsResult.data || []) : [];
        this.dashboardHireItems = hireResult.success ? (hireResult.data || []) : [];
        this.dashboardApplications = applicationsResult.success ? (applicationsResult.data || []) : [];

        try {
            this.dashboardPlatforms = await this.loadSocialPlatformsCache(true);
        } catch (error) {
            this.dashboardPlatforms = this.dashboardPlatforms || [];
        }
    };

    KomaginAdmin.prototype.renderDashboardCharts = function() {
        const projects = Array.isArray(this.dashboardProjects) ? this.dashboardProjects : [];
        const services = Array.isArray(this.dashboardServices) ? this.dashboardServices : [];
        const testimonials = Array.isArray(this.dashboardTestimonials) ? this.dashboardTestimonials : [];
        const team = Array.isArray(this.dashboardTeam) ? this.dashboardTeam : [];
        const contacts = Array.isArray(this.dashboardContacts) ? this.dashboardContacts : [];
        const subscribers = Array.isArray(this.dashboardSubscribers) ? this.dashboardSubscribers : [];
        const applications = Array.isArray(this.dashboardApplications) ? this.dashboardApplications : [];
        const jobs = Array.isArray(this.dashboardJobs) ? this.dashboardJobs : [];
        const documents = Array.isArray(this.dashboardDocuments) ? this.dashboardDocuments : [];
        const hireItems = Array.isArray(this.dashboardHireItems) ? this.dashboardHireItems : [];
        const platforms = Array.isArray(this.dashboardPlatforms) ? this.dashboardPlatforms : [];

        const contentData = [
            { label: 'Projects', value: projects.length, color: dashboardPalette[0] },
            { label: 'Services', value: services.length, color: dashboardPalette[1] },
            { label: 'Testimonials', value: testimonials.length, color: dashboardPalette[2] },
            { label: 'Team', value: team.length, color: dashboardPalette[3] }
        ];

        const projectCategoryCounts = projects.reduce((acc, project) => {
            const key = String(project.category || 'uncategorized').trim() || 'uncategorized';
            acc[key] = (acc[key] || 0) + 1;
            return acc;
        }, {});
        const projectCategoryData = Object.entries(projectCategoryCounts)
            .sort((a, b) => b[1] - a[1])
            .map(([key, value], index) => ({
                label: this.label(key),
                value,
                color: dashboardPalette[index % dashboardPalette.length]
            }));

        const volumeData = [
            { label: 'Contact Messages', value: contacts.length, color: dashboardPalette[0] },
            { label: 'Email Subscriptions', value: subscribers.length, color: dashboardPalette[1] },
            { label: 'Equipment Listed', value: hireItems.length, color: dashboardPalette[2] }
        ];

        const appStatusOrder = ['received', 'shortlisted', 'interview', 'hired', 'rejected'];
        const appChartData = appStatusOrder.map((status, index) => ({
            label: this.label(status),
            value: applications.filter(item => String(item.status || 'received') === status).length,
            color: dashboardPalette[index % dashboardPalette.length]
        }));

        const hireAvailabilityData = ['available', 'on_request', 'limited', 'booked'].map((status, index) => ({
            label: this.label(status),
            value: hireItems.filter(item => String(item.availability_status || 'available') === status).length,
            color: dashboardPalette[index % dashboardPalette.length]
        }));

        const readyChannels = platforms.filter(item => Number(item.posting_ready || 0) === 1).length;
        const verifiedOnly = platforms.filter(item => Number(item.posting_ready || 0) !== 1 && String(item.verification_status || '') === 'verified').length;
        const setupNeeded = Math.max(platforms.length - readyChannels - verifiedOnly, 0);
        const channelReadinessData = [
            { label: 'Ready to Post', value: readyChannels, color: dashboardPalette[2] },
            { label: 'Verified Only', value: verifiedOnly, color: dashboardPalette[1] },
            { label: 'Needs Setup', value: setupNeeded, color: dashboardPalette[7] }
        ];

        const publishingStatusData = [
            { label: 'Published Jobs', value: jobs.filter(item => String(item.status || '') === 'published').length, color: dashboardPalette[0] },
            { label: 'Live Documents', value: documents.filter(item => Number(item.is_visible || 0) === 1).length, color: dashboardPalette[1] },
            { label: 'Ready Channels', value: readyChannels, color: dashboardPalette[2] },
            { label: 'Visible Hire', value: hireItems.filter(item => Number(item.is_active || 0) === 1).length, color: dashboardPalette[3] }
        ];

        const buckets = this.getRecentMonthBuckets(6);
        const contactSeries = this.bucketRowsByMonth(contacts, 'created_at', buckets);
        const subscriberSeries = this.bucketRowsByMonth(subscribers, 'subscribed_at', buckets);
        const applicationSeries = this.bucketRowsByMonth(applications, 'created_at', buckets);
        const shortlistSeries = this.bucketRowsByMonth(applications.filter(item => String(item.status || '') === 'shortlisted'), 'reviewed_at', buckets);
        const interviewSeries = this.bucketRowsByMonth(applications.filter(item => String(item.status || '') === 'interview'), 'reviewed_at', buckets);

        this.renderDashboardDonut('dashboardContentMixChart', contentData, 'Managed Content');
        this.renderDashboardDonut('dashboardProjectCategoryChart', projectCategoryData, 'Projects');
        this.renderDashboardBars('dashboardVolumeChart', volumeData, 'items');
        this.renderDashboardLine('dashboardTrendChart', buckets.map(item => item.label), [
            { label: 'Contacts', values: contactSeries, color: dashboardPalette[0] },
            { label: 'Subscribers', values: subscriberSeries, color: dashboardPalette[1] }
        ]);
        this.renderDashboardBars('dashboardApplicationsChart', appChartData, 'applications');
        this.renderDashboardDonut('dashboardHireAvailabilityChart', hireAvailabilityData, 'Hire Fleet');
        this.renderDashboardDonut('dashboardChannelReadinessChart', channelReadinessData, 'Channels');
        this.renderDashboardLine('dashboardRecruitmentTrendChart', buckets.map(item => item.label), [
            { label: 'Applications', values: applicationSeries, color: dashboardPalette[3] },
            { label: 'Shortlisted', values: shortlistSeries, color: dashboardPalette[1] },
            { label: 'Interview', values: interviewSeries, color: dashboardPalette[2] }
        ]);
        this.renderDashboardBars('dashboardPublishingStatusChart', publishingStatusData, 'live items');
    };
})();

// DASHBOARD FINALIZATION PASS: streamline cards, pull dashboard data from direct sources, and render project category charts from live project records.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__dashboardFinalizationPass) return;
    KomaginAdmin.prototype.__dashboardFinalizationPass = true;

    const dashboardPalette = ['#1A3A5C', '#E8A317', '#27AE60', '#3498DB', '#E67E22', '#8E44AD', '#16A085', '#E74C3C'];

    KomaginAdmin.prototype.loadDashboardData = async function() {
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=get_stats`),
                'Dashboard data could not be loaded'
            );
            if (result.success) {
                const stats = result.data || {};
                this.setText('totalProjects', stats.total_projects || 0);
                this.setText('totalServices', stats.total_services || 0);
                this.setText('totalTestimonials', stats.total_testimonials || 0);
                this.setText('totalTeam', stats.total_team || 0);
                this.setText('totalContacts', stats.total_contacts || 0);
                this.setText('totalSubscribers', stats.total_subscribers || 0);
                this.setText('totalSubscribersStat', stats.total_subscribers || 0);
                this.setText('openJobs', stats.open_jobs || 0);
                this.setText('newApplications', stats.new_applications || 0);
                this.setText('staffCount', stats.staff_count || 0);
                this.setText('openAssets', stats.open_assets || 0);
                this.setText('pendingApprovals', stats.pending_approvals || 0);
                this.setText('activeBranches', stats.active_branches || 0);
            }
        } catch (error) {
            console.error('Error loading dashboard stats:', error);
        }

        await this.refreshDashboardDataFeeds();

        const subscribers = Array.isArray(this.dashboardSubscribers) ? this.dashboardSubscribers : [];
        const activeSubscribers = subscribers.filter(item => String(item.status || 'active') === 'active').length;
        const monthKey = `${new Date().getFullYear()}-${String(new Date().getMonth() + 1).padStart(2, '0')}`;
        const newThisMonth = subscribers.filter(item => String(item.subscribed_at || '').slice(0, 7) === monthKey).length;
        this.setText('activeSubscribers', activeSubscribers);
        this.setText('newSubscribersMonth', newThisMonth);

        this.renderDashboardCharts();
    };

    KomaginAdmin.prototype.refreshDashboardDataFeeds = async function() {
        const safeRead = async (url, fallbackMessage = 'Dashboard data could not be loaded') => {
            try {
                return await this.readAdminJsonResponse(fetch(url), fallbackMessage);
            } catch (error) {
                return { success: false, data: [] };
            }
        };

        const [
            projectsResult,
            servicesResult,
            testimonialsResult,
            teamResult,
            contactsResult,
            subscribersResult,
            jobsResult,
            documentsResult,
            hireResult,
            applicationsResult
        ] = await Promise.all([
            safeRead(`${this.apiBase}?action=get_projects`),
            safeRead(`${this.apiBase}?action=get_services`),
            safeRead(`${this.apiBase}?action=get_testimonials`),
            safeRead(`${this.apiBase}?action=get_team`),
            safeRead(`${this.apiBase}?action=get_contacts`),
            safeRead(`${this.apiBase}?action=get_subscribers`),
            safeRead(`${this.apiBase}?action=hr_get_jobs`),
            safeRead(`${this.apiBase}?action=documents_get_all`),
            safeRead(`${this.apiBase}?action=hire_items_get_all`),
            safeRead(`${this.apiBase}?action=hr_get_applications&status=all`)
        ]);

        this.dashboardProjects = projectsResult.success ? (projectsResult.data || []) : [];
        this.dashboardServices = servicesResult.success ? (servicesResult.data || []) : [];
        this.dashboardTestimonials = testimonialsResult.success ? (testimonialsResult.data || []) : [];
        this.dashboardTeam = teamResult.success ? (teamResult.data || []) : [];
        this.dashboardContacts = contactsResult.success ? (contactsResult.data || []) : [];
        this.dashboardSubscribers = subscribersResult.success ? (subscribersResult.data || []) : [];
        this.dashboardJobs = jobsResult.success ? (jobsResult.data || []) : [];
        this.dashboardDocuments = documentsResult.success ? (documentsResult.data || []) : [];
        this.dashboardHireItems = hireResult.success ? (hireResult.data || []) : [];
        this.dashboardApplications = applicationsResult.success ? (applicationsResult.data || []) : [];

        try {
            this.dashboardPlatforms = await this.loadSocialPlatformsCache(true);
        } catch (error) {
            this.dashboardPlatforms = this.dashboardPlatforms || [];
        }
    };

    KomaginAdmin.prototype.renderDashboardCharts = function() {
        const projects = Array.isArray(this.dashboardProjects) ? this.dashboardProjects : [];
        const services = Array.isArray(this.dashboardServices) ? this.dashboardServices : [];
        const testimonials = Array.isArray(this.dashboardTestimonials) ? this.dashboardTestimonials : [];
        const team = Array.isArray(this.dashboardTeam) ? this.dashboardTeam : [];
        const contacts = Array.isArray(this.dashboardContacts) ? this.dashboardContacts : [];
        const subscribers = Array.isArray(this.dashboardSubscribers) ? this.dashboardSubscribers : [];
        const applications = Array.isArray(this.dashboardApplications) ? this.dashboardApplications : [];
        const jobs = Array.isArray(this.dashboardJobs) ? this.dashboardJobs : [];
        const documents = Array.isArray(this.dashboardDocuments) ? this.dashboardDocuments : [];
        const hireItems = Array.isArray(this.dashboardHireItems) ? this.dashboardHireItems : [];
        const platforms = Array.isArray(this.dashboardPlatforms) ? this.dashboardPlatforms : [];

        const contentData = [
            { label: 'Projects', value: projects.length, color: dashboardPalette[0] },
            { label: 'Services', value: services.length, color: dashboardPalette[1] },
            { label: 'Testimonials', value: testimonials.length, color: dashboardPalette[2] },
            { label: 'Team', value: team.length, color: dashboardPalette[3] }
        ];

        const projectCategoryCounts = projects.reduce((acc, project) => {
            const key = String(project.category || 'uncategorized').trim() || 'uncategorized';
            acc[key] = (acc[key] || 0) + 1;
            return acc;
        }, {});
        const projectCategoryData = Object.entries(projectCategoryCounts)
            .sort((a, b) => b[1] - a[1])
            .map(([key, value], index) => ({
                label: this.label(key),
                value,
                color: dashboardPalette[index % dashboardPalette.length]
            }));

        const volumeData = [
            { label: 'Contact Messages', value: contacts.length, color: dashboardPalette[0] },
            { label: 'Email Subscriptions', value: subscribers.length, color: dashboardPalette[1] },
            { label: 'Equipment Listed', value: hireItems.length, color: dashboardPalette[2] }
        ];

        const appStatusOrder = ['received', 'shortlisted', 'interview', 'hired', 'rejected'];
        const appChartData = appStatusOrder.map((status, index) => ({
            label: this.label(status),
            value: applications.filter(item => String(item.status || 'received') === status).length,
            color: dashboardPalette[index % dashboardPalette.length]
        }));

        const hireAvailabilityData = ['available', 'on_request', 'limited', 'booked'].map((status, index) => ({
            label: this.label(status),
            value: hireItems.filter(item => String(item.availability_status || 'available') === status).length,
            color: dashboardPalette[index % dashboardPalette.length]
        }));

        const readyChannels = platforms.filter(item => Number(item.posting_ready || 0) === 1).length;
        const verifiedOnly = platforms.filter(item => Number(item.posting_ready || 0) !== 1 && String(item.verification_status || '') === 'verified').length;
        const setupNeeded = Math.max(platforms.length - readyChannels - verifiedOnly, 0);
        const channelReadinessData = [
            { label: 'Ready to Post', value: readyChannels, color: dashboardPalette[2] },
            { label: 'Verified Only', value: verifiedOnly, color: dashboardPalette[1] },
            { label: 'Needs Setup', value: setupNeeded, color: dashboardPalette[7] }
        ];

        const publishingStatusData = [
            { label: 'Published Jobs', value: jobs.filter(item => String(item.status || '') === 'published').length, color: dashboardPalette[0] },
            { label: 'Live Documents', value: documents.filter(item => Number(item.is_visible || 0) === 1).length, color: dashboardPalette[1] },
            { label: 'Ready Channels', value: readyChannels, color: dashboardPalette[2] },
            { label: 'Visible Hire', value: hireItems.filter(item => Number(item.is_active || 0) === 1).length, color: dashboardPalette[3] }
        ];

        const buckets = this.getRecentMonthBuckets(6);
        const contactSeries = this.bucketRowsByMonth(contacts, 'created_at', buckets);
        const subscriberSeries = this.bucketRowsByMonth(subscribers, 'subscribed_at', buckets);
        const applicationSeries = this.bucketRowsByMonth(applications, 'created_at', buckets);
        const shortlistSeries = this.bucketRowsByMonth(applications.filter(item => String(item.status || '') === 'shortlisted'), 'reviewed_at', buckets);
        const interviewSeries = this.bucketRowsByMonth(applications.filter(item => String(item.status || '') === 'interview'), 'reviewed_at', buckets);

        this.renderDashboardDonut('dashboardContentMixChart', contentData, 'Managed Content');
        this.renderDashboardDonut('dashboardProjectCategoryChart', projectCategoryData, 'Projects');
        this.renderDashboardBars('dashboardVolumeChart', volumeData, 'items');
        this.renderDashboardLine('dashboardTrendChart', buckets.map(item => item.label), [
            { label: 'Contacts', values: contactSeries, color: dashboardPalette[0] },
            { label: 'Subscribers', values: subscriberSeries, color: dashboardPalette[1] }
        ]);
        this.renderDashboardBars('dashboardApplicationsChart', appChartData, 'applications');
        this.renderDashboardDonut('dashboardHireAvailabilityChart', hireAvailabilityData, 'Hire Fleet');
        this.renderDashboardDonut('dashboardChannelReadinessChart', channelReadinessData, 'Channels');
        this.renderDashboardLine('dashboardRecruitmentTrendChart', buckets.map(item => item.label), [
            { label: 'Applications', values: applicationSeries, color: dashboardPalette[3] },
            { label: 'Shortlisted', values: shortlistSeries, color: dashboardPalette[1] },
            { label: 'Interview', values: interviewSeries, color: dashboardPalette[2] }
        ]);
        this.renderDashboardBars('dashboardPublishingStatusChart', publishingStatusData, 'live items');
    };
})();

// JOBS DASHBOARD FINAL OVERRIDE: keep safer JSON reads after all base/admin patches load.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__jobsDashboardFinalOverride) return;
    KomaginAdmin.prototype.__jobsDashboardFinalOverride = true;

    KomaginAdmin.prototype.refreshDashboardDataFeeds = async function() {
        const safeRead = async (url) => {
            try {
                return await this.readAdminJsonResponse(fetch(url), 'Dashboard data could not be loaded');
            } catch (error) {
                return { success: false, data: [] };
            }
        };
        const [
            projectsResult,
            servicesResult,
            testimonialsResult,
            teamResult,
            contactsResult,
            subscribersResult,
            jobsResult,
            documentsResult,
            hireResult,
            applicationsResult
        ] = await Promise.all([
            safeRead(`${this.apiBase}?action=get_projects`),
            safeRead(`${this.apiBase}?action=get_services`),
            safeRead(`${this.apiBase}?action=get_testimonials`),
            safeRead(`${this.apiBase}?action=get_team`),
            safeRead(`${this.apiBase}?action=get_contacts`),
            safeRead(`${this.apiBase}?action=get_subscribers`),
            safeRead(`${this.apiBase}?action=hr_get_jobs`),
            safeRead(`${this.apiBase}?action=documents_get_all`),
            safeRead(`${this.apiBase}?action=hire_items_get_all`),
            safeRead(`${this.apiBase}?action=hr_get_applications&status=all`)
        ]);
        this.dashboardProjects = projectsResult.success ? (projectsResult.data || []) : [];
        this.dashboardServices = servicesResult.success ? (servicesResult.data || []) : [];
        this.dashboardTestimonials = testimonialsResult.success ? (testimonialsResult.data || []) : [];
        this.dashboardTeam = teamResult.success ? (teamResult.data || []) : [];
        this.dashboardContacts = contactsResult.success ? (contactsResult.data || []) : [];
        this.dashboardSubscribers = subscribersResult.success ? (subscribersResult.data || []) : [];
        this.dashboardJobs = jobsResult.success ? (jobsResult.data || []) : (this.dashboardJobs || []);
        this.dashboardDocuments = documentsResult.success ? (documentsResult.data || []) : (this.dashboardDocuments || []);
        this.dashboardHireItems = hireResult.success ? (hireResult.data || []) : (this.dashboardHireItems || []);
        this.dashboardApplications = applicationsResult.success ? (applicationsResult.data || []) : (this.dashboardApplications || []);
        try {
            this.dashboardPlatforms = await this.loadSocialPlatformsCache(true);
        } catch (error) {
            this.dashboardPlatforms = this.dashboardPlatforms || [];
        }
    };
})();

// JOBS MODULE HARDENING: clean recent applications rendering and safer dashboard data reads.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__jobsModuleHardeningPatch) return;
    KomaginAdmin.prototype.__jobsModuleHardeningPatch = true;

    KomaginAdmin.prototype.loadRecentApplications = async function() {
        const container = document.getElementById('recentApplications');
        if (!container) return;
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=hr_get_applications&status=all`),
                'Recent applications could not be loaded'
            );
            if (!result.success) throw new Error(result.error || 'Recent applications could not be loaded');
            const rows = (result.data || []).slice(0, 5);
            if (!rows.length) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-file-signature"></i><p>No applications received yet.</p></div>`;
                return;
            }
            container.innerHTML = rows.map(app => `<div class="activity-item">
                <div class="activity-content">
                    <h4>${this.escapeHtml(app.applicant_name || 'Applicant')}</h4>
                    <p>${this.escapeHtml(app.job_title || 'Vacancy')} <span class="status-badge ${this.getApplicationStatusClass(app.status || 'received')}">${this.escapeHtml(this.label(app.status || 'received'))}</span></p>
                    <small>${app.created_at ? this.formatDate(app.created_at) : ''}</small>
                </div>
            </div>`).join('');
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><p>${this.escapeHtml(error.message)}</p></div>`;
        }
    };

    KomaginAdmin.prototype.refreshDashboardDataFeeds = async function() {
        const safeRead = async (url) => {
            try {
                return await this.readAdminJsonResponse(fetch(url), 'Dashboard data could not be loaded');
            } catch (error) {
                return { success: false, data: [] };
            }
        };
        const [
            projectsResult,
            servicesResult,
            testimonialsResult,
            teamResult,
            contactsResult,
            subscribersResult,
            jobsResult,
            documentsResult,
            hireResult,
            applicationsResult
        ] = await Promise.all([
            safeRead(`${this.apiBase}?action=get_projects`),
            safeRead(`${this.apiBase}?action=get_services`),
            safeRead(`${this.apiBase}?action=get_testimonials`),
            safeRead(`${this.apiBase}?action=get_team`),
            safeRead(`${this.apiBase}?action=get_contacts`),
            safeRead(`${this.apiBase}?action=get_subscribers`),
            safeRead(`${this.apiBase}?action=hr_get_jobs`),
            safeRead(`${this.apiBase}?action=documents_get_all`),
            safeRead(`${this.apiBase}?action=hire_items_get_all`),
            safeRead(`${this.apiBase}?action=hr_get_applications&status=all`)
        ]);
        this.dashboardProjects = projectsResult.success ? (projectsResult.data || []) : [];
        this.dashboardServices = servicesResult.success ? (servicesResult.data || []) : [];
        this.dashboardTestimonials = testimonialsResult.success ? (testimonialsResult.data || []) : [];
        this.dashboardTeam = teamResult.success ? (teamResult.data || []) : [];
        this.dashboardContacts = contactsResult.success ? (contactsResult.data || []) : [];
        this.dashboardSubscribers = subscribersResult.success ? (subscribersResult.data || []) : [];
        this.dashboardJobs = jobsResult.success ? (jobsResult.data || []) : (this.dashboardJobs || []);
        this.dashboardDocuments = documentsResult.success ? (documentsResult.data || []) : (this.dashboardDocuments || []);
        this.dashboardHireItems = hireResult.success ? (hireResult.data || []) : (this.dashboardHireItems || []);
        this.dashboardApplications = applicationsResult.success ? (applicationsResult.data || []) : (this.dashboardApplications || []);
        try {
            this.dashboardPlatforms = await this.loadSocialPlatformsCache(true);
        } catch (error) {
            this.dashboardPlatforms = this.dashboardPlatforms || [];
        }
    };
})();


// ============================================
// KOMAGIN LIMITED - UPGRADE v3.0 ADMIN MODULES
// ============================================
(function() {
    const oldGetUpgradeConfig = KomaginAdmin.prototype.getUpgradeConfig;
    KomaginAdmin.prototype.getUpgradeConfig = function() {
        const cfg = oldGetUpgradeConfig.call(this);
        Object.assign(cfg, {
            'social-posts': { container:'socialPostsContainer', list:'social_get_posts', save:'social_publish_post', del:'social_delete_post', title:'Social Post', card:true, columns:[['created_at','Date'],['title','Title'],['platforms','Platforms'],['status','Status'],['published_at','Published']], fields:[['title','Internal Title','text'],['content','Post Content','textarea',true],['platforms','Posting Channels','platform_multi',true],['media_path','Post Media','social_media_upload'],['link_url','Optional Website Link','url'],['hashtags','Hashtags','text'],['scheduled_at','Schedule At','datetime-local']], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Results" onclick="komaginAdmin.viewSocialResults('${r.id}')"><i class="fas fa-chart-simple"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Delete" onclick="komaginAdmin.runUpgradeAction('social_delete_post&id=${r.id}',null,'social-posts')"><i class="fas fa-trash"></i></button>` },
            'plant-hire-items': { container:'plantHireItemsContainer', list:'hire_items_get_all', save:'hire_item_save', del:'hire_item_delete', title:'Hire Item', card:true, cardStats:true, columns:[['name','Name'],['category','Category'],['availability_status','Availability'],['location','Location'],['featured','Featured']], fields:[['name','Equipment Name','text',true],['category','Category','select',false,['excavators','graders','rollers','loaders','dump_trucks','bulldozers','cranes','water_carts','compactors','survey_support','survey_equipment','generators','support_vehicles','other']],['short_description','Short Description','textarea',true],['specifications','Specifications','textarea'],['rate_note','Rate / Hire Note','text'],['location','Location','text'],['availability_status','Availability','select',false,['available','on_request','limited','booked']],['operator_option','Operator Option','select',false,['included','optional','not_included']],['delivery_option','Delivery Option','select',false,['available','on_request','pickup_only']],['image','Equipment Image','image_upload'],['tags','Tags','text'],['featured','Featured','select',false,['1','0']],['sort_order','Display Order','number'],['is_active','Visible','select',false,['1','0']]], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Edit" onclick="window.komaginAdmin && window.komaginAdmin.editHireItem('${r.id}')"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Delete" onclick="window.komaginAdmin && window.komaginAdmin.deleteHireItem('${r.id}')"><i class="fas fa-trash"></i></button>` },
            'branch-site-reports': { container:'branchSiteReportsContainer', list:'branch_reports_get', save:'branch_reports_save', title:'Site Report', cardStats:true, columns:[['branch_name','Branch'],['project_name','Project'],['report_date','Report Date'],['report_type','Type'],['workers_on_site','Workers'],['safety_incidents','Incidents'],['status','Status'],['submitted_by','Submitted By']], fields:[['branch_id','Branch','branch_select',true],['project_id','Project ID','text'],['report_date','Report Date','date',true],['report_type','Report Type','select',false,['daily','weekly','monthly','incident']],['weather','Weather','text'],['workers_on_site','Workers On Site','number'],['activities_done','Activities Done','textarea'],['issues_raised','Issues Raised','textarea'],['materials_used','Materials Used','textarea'],['equipment_used','Equipment Used','textarea'],['safety_incidents','Safety Incidents','number'],['incident_detail','Incident Detail','textarea'],['submitted_by','Submitted By','text']], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="View" onclick="komaginAdmin.viewBranchReport('${r.id}')"><i class="fas fa-eye"></i></button><button class="btn btn-sm btn-success icon-btn" title="Verify" onclick="komaginAdmin.runUpgradeAction('branch_reports_verify&id=${r.id}',null,'branch-site-reports')"><i class="fas fa-check"></i></button><button class="btn btn-sm btn-danger icon-btn" title="Flag" onclick="komaginAdmin.runUpgradeAction('branch_reports_flag&id=${r.id}',null,'branch-site-reports')"><i class="fas fa-flag"></i></button><button class="btn btn-sm btn-outline icon-btn" title="PDF" onclick="komaginAdmin.generateDocument('branch_generate_site_report&id=${r.id}')"><i class="fas fa-file-pdf"></i></button>` },
            'branch-milestones': { container:'branchMilestonesContainer', list:'branch_milestones_get', save:'branch_milestones_save', title:'Milestone', columns:[['project_name','Project'],['title','Milestone'],['weight_percent','Weight'],['due_date','Due Date'],['assigned_to','Assigned To'],['status','Status'],['evidence_file','Evidence']], fields:[['project_id','Project ID','text',true],['branch_id','Branch','branch_select',true],['title','Title','text',true],['description','Description','textarea'],['due_date','Due Date','date'],['weight_percent','Weight %','number'],['assigned_to','Assigned To','text'],['blockers','Blockers','textarea'],['status','Status','select',false,['pending','in_progress','completed','overdue','blocked']]], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Edit" onclick="komaginAdmin.openUpgradeForm('branch-milestones','${r.id}')"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-success icon-btn" title="Complete" onclick="komaginAdmin.runUpgradeAction('branch_milestones_complete&id=${r.id}',null,'branch-milestones')"><i class="fas fa-check"></i></button>` },
            'branch-rfis': { container:'branchRfisContainer', list:'branch_rfis_get', save:'branch_rfis_save', title:'RFI', cardStats:true, columns:[['rfi_number','RFI Number'],['branch_name','Branch'],['project_name','Project'],['subject','Subject'],['priority','Priority'],['status','Status'],['raised_by','Raised By'],['due_date','Due Date']], fields:[['branch_id','Branch','branch_select',true],['project_id','Project ID','text'],['rfi_number','RFI Number','text'],['subject','Subject','text',true],['description','Description','textarea',true],['priority','Priority','select',false,['low','medium','high','urgent']],['due_date','Due Date','date'],['raised_by','Raised By','text'],['answer','Answer','textarea']], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Answer" onclick="komaginAdmin.answerRfi('${r.id}')"><i class="fas fa-reply"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Letter" onclick="komaginAdmin.generateDocument('branch_generate_rfi_response&id=${r.id}')"><i class="fas fa-envelope-open-text"></i></button><button class="btn btn-sm btn-success icon-btn" title="Close" onclick="komaginAdmin.runUpgradeAction('branch_rfis_close&id=${r.id}',null,'branch-rfis')"><i class="fas fa-check"></i></button>` },
            'branch-kpis': { container:'branchKpisContainer', list:'branch_kpis_get', title:'Branch KPI', columns:[['branch_name','Branch'],['period','Period'],['projects_active','Active'],['projects_completed','Completed'],['projects_delayed','Delayed'],['budget_spent','Spent'],['avg_milestone_completion','Milestone %'],['safety_incidents','Incidents'],['client_satisfaction','Rating']], actions:(r)=>`<button class="btn btn-sm btn-outline icon-btn" title="Notes" onclick="komaginAdmin.editKpiNotes('${r.id}')"><i class="fas fa-note-sticky"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Report" onclick="komaginAdmin.generateDocument('branch_generate_kpi_report&id=${r.id}')"><i class="fas fa-file-lines"></i></button>` },
            'activity-log': { container:'activityLogContainer', list:'activity_logs_get', title:'Activity Log', columns:[['created_at','Date/Time'],['username','User'],['action','Action'],['details','Details'],['ip_address','IP Address']] }
        });
        return cfg;
    };

    const oldLoadSectionDataV3 = KomaginAdmin.prototype.loadSectionData;
    KomaginAdmin.prototype.loadSectionData = function(section) {
        if (section === 'social-setup') return this.loadSocialSetup();
        if (section === 'branch-kpis') {
            const p = document.getElementById('kpiPeriod');
            if (p && !p.value) p.value = new Date().toISOString().slice(0,7);
        }
        return oldLoadSectionDataV3.call(this, section);
    };

    const oldInitV3 = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInitV3.call(this);
        const kpi = document.getElementById('generateKpisBtn');
        if (kpi) kpi.addEventListener('click', () => this.runUpgradeAction(`branch_kpis_generate&period=${document.getElementById('kpiPeriod')?.value || new Date().toISOString().slice(0,7)}`, null, 'branch-kpis'));
        const purge = document.getElementById('purgeLogsBtn');
        if (purge) purge.addEventListener('click', () => this.runUpgradeAction('activity_logs_purge', null, 'activity-log'));
    };

    KomaginAdmin.prototype.loadSocialPlatformsCache = async function(force = false) {
        if (!force && Array.isArray(this._socialPlatforms) && this._socialPlatforms.length) return this._socialPlatforms;
        const result = await this.readAdminJsonResponse(
            fetch(`${this.apiBase}?action=social_get_platforms`),
            'Social platforms could not be loaded'
        );
        if (!result.success) throw new Error(result.error || 'Could not load platforms');
        this._socialPlatforms = result.data || [];
        return this._socialPlatforms;
    };

    KomaginAdmin.prototype.bindSocialSetupActions = function(container) {
        if (!container || container.dataset.socialSetupBound === '1') return;
        container.addEventListener('click', event => {
            const button = event.target.closest('[data-social-setup-action]');
            if (!button) return;
            const action = String(button.getAttribute('data-social-setup-action') || '').trim();
            const platform = String(button.getAttribute('data-platform') || '').trim();
            if (!platform) return;
            if (action === 'configure') {
                this.configureSocial(platform);
                return;
            }
            if (action === 'verify') {
                this.runUpgradeAction(`social_test_connection&platform=${encodeURIComponent(platform)}`, null, 'social-setup');
                return;
            }
            if (action === 'disconnect') {
                this.runUpgradeAction(`social_disconnect_platform&platform=${encodeURIComponent(platform)}`, null, 'social-setup');
            }
        });
        container.dataset.socialSetupBound = '1';
    };

    KomaginAdmin.prototype.loadSocialSetup = async function() {
        const container = document.getElementById('socialSetupContainer');
        if (!container) return;
        this.showLoading(true);
        try {
            const rows = await this.loadSocialPlatformsCache(true);
            if (!rows.length) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-share-nodes"></i><p>No social platforms are available yet.</p></div>`;
                return;
            }
            container.innerHTML = `<div class="soc-card-list">${rows.map(p => {
                const enabledBadge = p.is_enabled == 1
                    ? `<span class="soc-badge soc-badge-ok"><i class="fas fa-circle"></i> Enabled</span>`
                    : `<span class="soc-badge soc-badge-off"><i class="fas fa-circle"></i> Disabled</span>`;
                const verifyBadge = p.verification_status === 'verified'
                    ? `<span class="soc-badge soc-badge-ok"><i class="fas fa-check"></i> Verified</span>`
                    : p.verification_status === 'failed'
                    ? `<span class="soc-badge soc-badge-fail"><i class="fas fa-xmark"></i> Failed</span>`
                    : `<span class="soc-badge soc-badge-pend"><i class="fas fa-clock"></i> Pending</span>`;
                const readyBadge = p.posting_ready
                    ? `<span class="soc-badge soc-badge-ready"><i class="fas fa-bolt"></i> Ready</span>`
                    : '';
                return `<div class="soc-row-card">
                    <div class="soc-row-icon"><i class="fab ${this.escapeHtml(p.icon || 'fa-share-alt')}"></i></div>
                    <div class="soc-row-info">
                        <div class="soc-row-top">
                            <span class="soc-row-name">${this.escapeHtml(p.display_name)}</span>
                            ${enabledBadge}${verifyBadge}${readyBadge}
                        </div>
                        <div class="soc-row-account"><i class="fas fa-at"></i>${this.escapeHtml(p.account_name || 'No account name saved yet')}</div>
                        <div class="soc-row-msg">${this.escapeHtml(p.verification_message || 'Run verification after saving credentials.')}</div>
                    </div>
                    <div class="soc-row-actions">
                        <button class="btn btn-outline" type="button" title="Configure" aria-label="Configure" data-social-setup-action="configure" data-platform="${this.escapeHtml(p.platform || '')}"><i class="fas fa-sliders"></i> Configure</button>
                        <button class="btn btn-outline" type="button" title="Verify" aria-label="Verify" data-social-setup-action="verify" data-platform="${this.escapeHtml(p.platform || '')}"><i class="fas fa-circle-check"></i> Verify</button>
                        <button class="btn btn-danger" type="button" title="Disconnect" aria-label="Disconnect" data-social-setup-action="disconnect" data-platform="${this.escapeHtml(p.platform || '')}"><i class="fas fa-unlink"></i> Disconnect</button>
                    </div>
                </div>`;
            }).join('')}</div>`;
            this.bindSocialSetupActions(container);
        } catch(error) {
            container.innerHTML = `<div class="empty-state"><p>${this.escapeHtml(error.message)}</p></div>`;
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.configureSocial = function(platform) {
        const current = (this._socialPlatforms || []).find(item => item.platform === platform) || {};
        const pageLabel = current.page_label || 'Page / Account ID';
        const pagePlaceholder = current.page_placeholder || 'Enter the account identifier';
        const missing = Array.isArray(current.missing_fields) && current.missing_fields.length
            ? `<div class="module-card" style="margin-bottom:16px;"><strong>Missing for verification:</strong> ${this.escapeHtml(current.missing_fields.join(', '))}</div>`
            : '';
        document.getElementById('upgradeModalTitle').textContent = `Configure ${this.label(platform)}`;
        document.getElementById('upgradeModalBody').innerHTML = `<form id="socialConfigForm">
            <input type="hidden" name="platform" value="${platform}">
            <p class="module-card">${this.escapeHtml(current.description || `Generate your access token from the ${this.label(platform)} developer portal and paste it here.`)} This panel is designed for credential linking and verification.</p>
            ${missing}
            <div class="form-grid">
                <div class="form-group"><label>Account Name</label><input name="account_name" value="${this.escapeHtml(current.account_name || '')}" placeholder="Public-facing account name"></div>
                <div class="form-group"><label>${this.escapeHtml(pageLabel)}</label><input name="page_id" value="${this.escapeHtml(current.page_id || '')}" placeholder="${this.escapeHtml(pagePlaceholder)}"></div>
                <div class="form-group" style="grid-column:1/-1"><label>Access Token</label><textarea name="access_token" placeholder="Paste the long-lived access token only when updating it.">${''}</textarea><small>Leave blank to keep the saved token.</small></div>
                <div class="form-group"><label>API Key / Client ID</label><textarea name="api_key" placeholder="Paste the client ID or API key only when updating it.">${''}</textarea></div>
                <div class="form-group"><label>API Secret</label><textarea name="api_secret" placeholder="Paste the client secret only when updating it.">${''}</textarea></div>
                <div class="form-group"><label>Enabled</label><select name="is_enabled"><option value="1" ${String(current.is_enabled ?? 1) === '1' ? 'selected' : ''}>Enabled</option><option value="0" ${String(current.is_enabled ?? 1) === '0' ? 'selected' : ''}>Disabled</option></select></div>
                <div class="form-group"><label>Token Expiry (Optional)</label><input type="datetime-local" name="expires_at" value="${this.escapeHtml(current.expires_at ? String(current.expires_at).replace(' ', 'T').slice(0, 16) : '')}"></div>
                <div class="form-group" style="grid-column:1/-1"><label>Verification Notes</label><textarea disabled>${this.escapeHtml(current.verification_message || 'Run verification after saving your credentials.')}</textarea></div>
            </div>
            <div class="form-actions"><button class="btn btn-primary" type="submit">Save</button></div>
        </form>`;
        document.getElementById('socialConfigForm').addEventListener('submit', e => { e.preventDefault(); this.saveUpgradeForm('social-config', new FormData(e.target)); });
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    const oldSaveUpgradeFormV3 = KomaginAdmin.prototype.saveUpgradeForm;
    KomaginAdmin.prototype.saveUpgradeForm = async function(section, formData) {
        if (section === 'social-config') {
            await this.runUpgradeAction('social_save_platform_config', Object.fromEntries(formData.entries()), 'social-setup');
            this.hideModal('upgradeModal');
            return;
        }
        if (section === 'social-posts') {
            const data = Object.fromEntries(formData.entries());
            data.platforms = formData.getAll('platforms').map(item => String(item || '').trim()).filter(Boolean);
            if (!data.platforms.length) {
                this.showError('Select at least one verified posting channel.');
                return;
            }
            const mediaPath = String(data.media_path || '').trim();
            data.media_type = mediaPath ? (data.media_type || 'image') : 'none';
            const action = data.scheduled_at ? 'social_schedule_post' : 'social_publish_post';
            await this.runUpgradeAction(action, data, 'social-posts');
            this.hideModal('upgradeModal');
            return;
        }
        return oldSaveUpgradeFormV3.call(this, section, formData);
    };

    KomaginAdmin.prototype.answerRfi = function(id) {
        const answer = prompt('Enter the official RFI answer');
        if (answer) this.runUpgradeAction('branch_rfis_answer', {id, answer}, 'branch-rfis');
    };

    KomaginAdmin.prototype.editKpiNotes = function(id) {
        const notes = prompt('Add KPI notes');
        const rating = prompt('Client satisfaction rating 1-5 (optional)');
        this.runUpgradeAction('branch_kpis_update_notes', {id, notes: notes || '', client_satisfaction: rating || null}, 'branch-kpis');
    };

    KomaginAdmin.prototype.openSocialPostForm = async function(id = null) {
        await this.loadSocialPlatformsCache();
        const posts = this['_social-postsData'] || [];
        const row = id ? posts.find(p => p.id === id) || {} : {};
        const platforms = this._socialPlatforms || [];
        const selected = (() => {
            const v = row.platforms;
            if (Array.isArray(v)) return v;
            if (typeof v === 'string' && v.trim()) {
                try { const p = JSON.parse(v); if (Array.isArray(p)) return p; } catch(e) {}
                return v.split(',').map(s => s.trim()).filter(Boolean);
            }
            return [];
        })();
        const mediaPath = String(row.media_path || '').trim();
        const mediaType = String(row.media_type || (mediaPath ? 'image' : 'none')).trim() || 'none';
        const mediaUrl = mediaPath ? this.resolveAdminAssetUrl(row.media_url || mediaPath) : '';
        const mediaLabel = mediaPath ? mediaPath.split('/').pop() : 'Upload an image or video from your PC, or choose one from the media library.';
        const mediaPreview = mediaUrl
            ? (mediaType === 'video'
                ? `<video controls preload="metadata" style="width:100%;max-height:220px;object-fit:contain;border-radius:8px;background:#0f1720;"><source src="${this.escapeHtml(mediaUrl)}"></video>`
                : `<img src="${this.escapeHtml(mediaUrl)}" alt="${this.escapeHtml(row.title || 'Social post media')}" style="width:100%;max-height:220px;object-fit:cover;border-radius:8px">`)
            : '<i class="fas fa-photo-film"></i><span>No media uploaded</span>';
        document.getElementById('upgradeModalTitle').textContent = id ? 'Edit Social Post' : 'Compose Social Post';
        document.getElementById('upgradeModalBody').innerHTML = `<form id="socialPostComposeForm" enctype="multipart/form-data">
            <input type="hidden" name="id" value="${this.escapeHtml(row.id || '')}">
            <div class="form-grid">
                <div class="form-group" style="grid-column:1/-1"><label>Internal Title</label><input name="title" value="${this.escapeHtml(row.title || '')}" placeholder="Optional label for this post"></div>
                <div class="form-group" style="grid-column:1/-1"><label>Post Content <span class="req">*</span></label><textarea name="content" rows="6" required placeholder="Write the social media post caption here">${this.escapeHtml(row.content || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Posting Channels <span class="req">*</span></label>
                    <div class="sp-channels-grid">
                        ${platforms.map(p => `<label class="sp-channel-label${!p.posting_ready ? '" style="opacity:0.65;cursor:not-allowed' : ''}" title="${p.posting_ready ? '' : 'Verify this channel first'}">
                            <input type="checkbox" name="platforms" value="${this.escapeHtml(p.platform)}" ${selected.includes(p.platform) ? 'checked' : ''} ${!p.posting_ready ? 'disabled' : ''}>
                            <i class="fab ${this.escapeHtml(p.icon || 'fa-share-alt')}"></i>
                            ${this.escapeHtml(p.display_name)}
                        </label>`).join('') || '<p style="color:var(--text-light);font-size:0.82rem;">Configure social channels in Social Platform Setup first.</p>'}
                    </div>
                </div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Post Media</label>
                    <div class="image-upload-container social-media-upload">
                        <div class="image-preview ${mediaUrl ? 'has-image' : ''}" id="socialMediaPreview">${mediaPreview}</div>
                        <input type="hidden" name="media_path" value="${this.escapeHtml(mediaPath)}">
                        <input type="hidden" name="media_type" value="${this.escapeHtml(mediaType)}">
                        <input type="file" id="socialMediaInput" class="social-media-input file-input" accept="image/*,video/*">
                        <small id="socialMediaLabel">${this.escapeHtml(mediaLabel)}</small>
                        <small class="social-media-upload-note">Use the same media you want published to the live platform. The Media Library button is for saved image assets; direct upload supports both images and videos.</small>
                        <div class="image-upload-actions">
                            <button type="button" class="btn btn-outline" id="socialMediaUploadTrigger"><i class="fas fa-upload"></i> Choose File</button>
                            <button type="button" class="btn btn-outline" id="chooseSocialMediaLibraryBtn"><i class="fas fa-photo-video"></i> Media Library</button>
                            <button type="button" class="btn btn-secondary" id="clearSocialMediaBtn"><i class="fas fa-xmark"></i> Remove Media</button>
                        </div>
                    </div>
                </div>
                <div class="form-group"><label>Hashtags</label><input name="hashtags" value="${this.escapeHtml(row.hashtags || '')}" placeholder="#komagin #construction"></div>
                <div class="form-group"><label>Website Link</label><input type="url" name="link_url" value="${this.escapeHtml(row.link_url || '')}" placeholder="https://..."></div>
                <div class="form-group"><label>Schedule At</label><input type="datetime-local" name="scheduled_at" value="${this.escapeHtml(row.scheduled_at ? String(row.scheduled_at).replace(' ','T').slice(0,16) : '')}"></div>
            </div>
            <div class="sp-compose-actions">
                <button type="button" class="btn btn-secondary" onclick="komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> ${id ? 'Save Post' : 'Publish Post'}</button>
            </div>
        </form>`;
        document.getElementById('socialPostComposeForm').addEventListener('submit', event => {
            event.preventDefault();
            this.saveUpgradeForm('social-posts', new FormData(event.target));
        });
        const composeForm = document.getElementById('socialPostComposeForm');
        const socialMediaInput = composeForm?.querySelector('#socialMediaInput');
        const uploadTrigger = composeForm?.querySelector('#socialMediaUploadTrigger');
        const libraryTrigger = composeForm?.querySelector('#chooseSocialMediaLibraryBtn');
        const clearTrigger = composeForm?.querySelector('#clearSocialMediaBtn');
        if (uploadTrigger && socialMediaInput) {
            uploadTrigger.addEventListener('click', () => socialMediaInput.click());
        }
        if (socialMediaInput) {
            socialMediaInput.addEventListener('change', () => this.uploadSocialMediaFile(socialMediaInput));
        }
        if (libraryTrigger) {
            libraryTrigger.addEventListener('click', () => this.openSocialMediaLibraryPicker(composeForm));
        }
        if (clearTrigger) {
            clearTrigger.addEventListener('click', () => this.clearSocialMediaSelection(composeForm));
        }
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.viewSocialResults = function(id) {
        const row = (this['_social-postsData'] || []).find(p => p.id === id);
        const results = row?.results || [];
        const media = row ? this.renderSocialPostMediaPreview(row) : '';
        const platforms = this.formatSocialPlatforms(row?.platforms || []);
        document.getElementById('upgradeModalTitle').textContent = 'Social Post Results';
        document.getElementById('upgradeModalBody').innerHTML = `
            ${row ? `<div class="service-card" style="margin-bottom:16px;">${media}<div class="service-header"><div class="service-icon"><i class="fas fa-share-alt"></i></div><h4>${this.escapeHtml(row.title || 'Social Post')}</h4></div><p class="service-description">${this.escapeHtml(row.content || '')}</p><div class="service-meta"><span><i class="fas fa-share-nodes"></i> ${this.escapeHtml(platforms || 'No platforms')}</span><span><i class="fas fa-photo-film"></i> ${this.escapeHtml(this.label(row.media_type || 'none'))}</span></div></div>` : ''}
            ${results.length ? `<table class="articles-table"><thead><tr><th>Platform</th><th>Status</th><th>Error</th><th>URL</th></tr></thead><tbody>${results.map(r=>`<tr><td>${this.escapeHtml(r.platform)}</td><td>${this.renderCell('status',r.status,r)}</td><td>${this.escapeHtml(r.error_message||'')}</td><td>${r.post_url?`<a href="${r.post_url}" target="_blank">Open</a>`:'-'}</td></tr>`).join('')}</tbody></table>` : '<p>No results recorded yet for this social post.</p>'}
        `;
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    // Hook openUpgradeForm so social-posts uses the custom compose form
    const _oldOpenUpgradeFormSocial = KomaginAdmin.prototype.openUpgradeForm;
    KomaginAdmin.prototype.openUpgradeForm = function(section, id = null) {
        if (section === 'social-posts') return this.openSocialPostForm(id);
        if (section === 'partners') return this.openPartnerEnquiryEditor(id);
        return _oldOpenUpgradeFormSocial.call(this, section, id);
    };

})();

// Final newsletter binding runs after all legacy patches and removes older click handlers.
(function(){
    if (typeof KomaginAdmin === 'undefined' || KomaginAdmin.prototype.__newsletterTemplateFinalFix) return;
    KomaginAdmin.prototype.__newsletterTemplateFinalFix = true;
    const oldInit = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInit.call(this);
        const oldBtn = document.getElementById('newsletterTemplateBtn');
        if (!oldBtn || oldBtn.dataset.finalTemplateBound === '1') return;
        const btn = oldBtn.cloneNode(true);
        oldBtn.parentNode.replaceChild(btn, oldBtn);
        btn.dataset.finalTemplateBound = '1';
        btn.addEventListener('click', () => {
            const today = new Date().toLocaleDateString('en-AU', { day:'numeric', month:'long', year:'numeric' });
            const subject = document.getElementById('newsletterSubject');
            const content = document.getElementById('newsletterContent');
            if (subject) subject.value = 'Komagin Limited - Project & Operations Update | ' + today;
            if (content) content.value = this.buildNewsletterTemplate ? this.buildNewsletterTemplate(today) : '';
            this.showSuccess('Decorated newsletter template loaded');
        });
    };
})();

// KOMAGIN NEWSLETTER TEMPLATE FIX: replace legacy broken text with a clean decorated template.
(function(){
    if (typeof KomaginAdmin === 'undefined' || KomaginAdmin.prototype.__newsletterTemplateFix) return;
    KomaginAdmin.prototype.__newsletterTemplateFix = true;

    KomaginAdmin.prototype.buildNewsletterTemplate = function(today) {
        return `Dear Valued Partners and Subscribers,

We are pleased to share the latest update from Komagin Limited, covering project progress, operations, safety, and company announcements.

============================================================
KOMAGIN LIMITED
Civil, Structural & Infrastructure Engineering
============================================================

PROJECT HIGHLIGHTS
------------------------------------------------------------
Project Name: [Enter project name]
Branch / Location: [Enter location]
Progress: [e.g. 65% complete]
Key Achievement: [Describe milestone reached]
Expected Completion: [Enter date]

OPERATIONS UPDATE
------------------------------------------------------------
Safety Record: [e.g. 30 days incident-free]
Equipment Deployed: [List key assets]
Labour on Site: [Number of workers]
Upcoming Work: [Next phase or milestone]

COMPANY NEWS
------------------------------------------------------------
[Add company news, announcements, milestones, CSR notes, or upcoming events here]

ATTACHED DOCUMENTS
------------------------------------------------------------
1. [Document name - e.g. Monthly Site Report - ${today}]
2. [Document name - e.g. Financial Summary]
3. [Document name - e.g. Safety Compliance Certificate]

NEXT STEPS
------------------------------------------------------------
[Add next action, meeting date, request for feedback, or contact instruction here]

Thank you for your continued support and partnership with Komagin Limited.

For enquiries, contact us at: info@komagin.com | +675 XXX XXXX

Warm regards,

The Komagin Limited Team
Komagin Limited | Port Moresby, Papua New Guinea
www.komagin.com.pg

============================================================
To unsubscribe from this newsletter, reply with UNSUBSCRIBE.`;
    };

    const oldInit = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInit.call(this);
        const oldBtn = document.getElementById('newsletterTemplateBtn');
        if (!oldBtn || oldBtn.dataset.cleanTemplateBound === '1') return;
        const btn = oldBtn.cloneNode(true);
        oldBtn.parentNode.replaceChild(btn, oldBtn);
        btn.dataset.cleanTemplateBound = '1';
        btn.addEventListener('click', () => {
            const today = new Date().toLocaleDateString('en-AU', { day:'numeric', month:'long', year:'numeric' });
            const subject = document.getElementById('newsletterSubject');
            const content = document.getElementById('newsletterContent');
            if (subject) subject.value = 'Komagin Limited - Project & Operations Update | ' + today;
            if (content) content.value = this.buildNewsletterTemplate(today);
            this.showSuccess('Decorated newsletter template loaded');
        });
    };
})();

// KOMAGIN BLOG CMS: public website blog posts are managed by the main admin.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__blogCmsPatch) return;
    KomaginAdmin.prototype.__blogCmsPatch = true;

    const oldLoadSectionData = KomaginAdmin.prototype.loadSectionData;
    KomaginAdmin.prototype.loadSectionData = function(section) {
        if (section === 'blog-posts') return this.loadBlogPosts();
        return oldLoadSectionData.call(this, section);
    };

    const oldInit = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInit.call(this);
        const addBtn = document.getElementById('addBlogPostBtn');
        if (addBtn && !addBtn.dataset.bound) {
            addBtn.dataset.bound = '1';
            addBtn.addEventListener('click', () => this.openBlogPostForm());
        }
    };

    KomaginAdmin.prototype.loadBlogPosts = async function() {
        const container = document.getElementById('blogPostsContainer');
        if (!container) return;
        this.showLoading(true);
        try {
            const result = await fetch(`${this.apiBase}?action=blog_posts_get_all`).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'Could not load blog posts');
            const posts = result.data || [];
            this._blogPosts = posts;
            container.innerHTML = this.renderUpgradeStats(result.stats) + this.renderBlogPosts(posts);
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-newspaper"></i><p>${this.escapeHtml(error.message)}</p></div>`;
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.renderBlogPosts = function(posts) {
        if (!posts.length) {
            return `<div class="articles-list"><div class="empty-state"><i class="fas fa-newspaper"></i><h3>No blog posts yet</h3><p>Create the first website news post or announcement.</p></div></div>`;
        }
        const statusBadge = (status) => {
            if (status === 'published') return 'blog-badge-pub';
            if (status === 'archived') return 'blog-badge-arch';
            return 'blog-badge-draft';
        };
        return `<div class="blog-card-list">${posts.map(post => {
            const thumb = post.featured_image_url
                ? `<img class="blog-row-thumb" src="${this.escapeHtml(post.featured_image_url)}" alt="${this.escapeHtml(post.title)}">`
                : `<div class="blog-row-placeholder"><i class="fas fa-newspaper"></i></div>`;
            const attBadge = post.attachment_url
                ? `<span class="blog-row-badge blog-badge-att"><i class="fas fa-paperclip"></i> Attachment</span>`
                : '';
            return `<div class="blog-row-card">
                ${thumb}
                <div class="blog-row-info">
                    <div class="blog-row-top">
                        <span class="blog-row-title">${this.escapeHtml(post.title)}</span>
                        <span class="blog-row-badge ${statusBadge(post.status || 'draft')}">${this.escapeHtml(this.label(post.status || 'draft'))}</span>
                        <span class="blog-row-badge blog-badge-cat">${this.escapeHtml(this.label(post.category || 'news'))}</span>
                        ${attBadge}
                    </div>
                    <div class="blog-row-meta">
                        <span><i class="fas fa-calendar"></i>${post.display_date ? this.formatDate(post.display_date) : '-'}</span>
                    </div>
                    ${post.excerpt ? `<div class="blog-row-excerpt">${this.escapeHtml(post.excerpt)}</div>` : ''}
                </div>
                <div class="blog-row-actions">
                    <button class="btn btn-sm btn-outline icon-btn" type="button" title="Edit post" onclick="window.komaginAdmin.openBlogPostForm('${post.id}')"><i class="fas fa-pen-to-square"></i></button>
                    <a class="btn btn-sm btn-outline icon-btn" title="View on website" href="../index.html#blog" target="_blank" rel="noopener"><i class="fas fa-eye"></i></a>
                    <button class="btn btn-sm btn-danger icon-btn" type="button" title="Delete post" onclick="window.komaginAdmin.deleteBlogPost('${post.id}')"><i class="fas fa-trash"></i></button>
                </div>
            </div>`;
        }).join('')}</div>`;
    };

    KomaginAdmin.prototype.openBlogPostForm = function(id = null) {
        const post = id ? (this._blogPosts || []).find(item => item.id === id) || {} : {};
        document.getElementById('upgradeModalTitle').textContent = id ? 'Edit Blog Post' : 'New Blog Post';
        document.getElementById('upgradeModalBody').innerHTML = `<form id="blogPostForm" enctype="multipart/form-data">
            <input type="hidden" name="id" value="${this.escapeHtml(post.id || '')}">
            <div class="form-grid">
                <div class="form-group"><label>Post Title *</label><input name="title" required value="${this.escapeHtml(post.title || '')}" placeholder="e.g. Project update, company news, safety announcement"></div>
                <div class="form-group"><label>Slug</label><input name="slug" value="${this.escapeHtml(post.slug || '')}" placeholder="Auto-created from title if blank"></div>
                <div class="form-group"><label>Category</label><select name="category">
                    ${['news','announcement','project_update','safety','community','recruitment'].map(c => `<option value="${c}" ${String(post.category || 'news') === c ? 'selected' : ''}>${this.label(c)}</option>`).join('')}
                </select></div>
                <div class="form-group"><label>Status</label><select name="status">
                    ${['draft','published','archived'].map(s => `<option value="${s}" ${String(post.status || 'draft') === s ? 'selected' : ''}>${this.label(s)}</option>`).join('')}
                </select></div>
                <div class="form-group" style="grid-column:1/-1"><label>Short Summary</label><textarea name="excerpt" rows="3" placeholder="Brief text shown on the blog listing cards">${this.escapeHtml(post.excerpt || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label>Post Content *</label><textarea name="content" rows="9" required placeholder="Write the public blog content here">${this.escapeHtml(post.content || '')}</textarea></div>
                <div class="form-group">
                    <label>Featured Image</label>
                    <div class="image-upload-actions">
                        <button type="button" class="btn btn-outline" id="chooseBlogFeaturedImageBtn"><i class="fas fa-upload"></i> Upload File</button>
                    </div>
                    <input type="file" name="featured_image" id="blogFeaturedImageInput" accept="image/*" class="file-input">
                    <input type="hidden" name="featured_image_path" id="blogFeaturedImagePath" value="${this.escapeHtml(post.featured_image || '')}">
                    <small id="blogFeaturedImageNote" data-media-library-note="1">${post.featured_image ? 'Current: ' + this.escapeHtml(post.featured_image) : 'Optional image for the blog card and article header.'}</small>
                </div>
                <div class="form-group">
                    <label>Attachment</label>
                    <div class="image-upload-actions blog-attachment-actions">
                        <button type="button" class="btn btn-outline" id="chooseBlogAttachmentBtn"><i class="fas fa-upload"></i> Upload File</button>
                        <button type="button" class="btn btn-outline" id="chooseBlogAttachmentLibraryBtn"><i class="fas fa-photo-video"></i> Media Library</button>
                    </div>
                    <input type="file" name="attachment" id="blogAttachmentInput" accept=".pdf,.doc,.docx,image/*" class="file-input">
                    <input type="hidden" name="attachment_path" id="blogAttachmentPath" value="${this.escapeHtml(post.attachment_path || '')}">
                    <small id="blogAttachmentNote">${post.attachment_name ? 'Current: ' + this.escapeHtml(post.attachment_name) : 'Optional PDF, Word document, or image.'}</small>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Blog Post</button>
            </div>
        </form>`;
        document.getElementById('blogPostForm').addEventListener('submit', event => {
            event.preventDefault();
            this.saveBlogPost(new FormData(event.target));
        });
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.bindBlogAssetChoosers(post);
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.bindBlogAssetChoosers = function(post = {}) {
        const featuredInput = document.getElementById('blogFeaturedImageInput');
        const featuredBtn = document.getElementById('chooseBlogFeaturedImageBtn');
        const featuredPath = document.getElementById('blogFeaturedImagePath');
        const featuredNote = document.getElementById('blogFeaturedImageNote');
        const featuredPreview = featuredInput ? this.ensureImagePreviewBlock(featuredInput) : null;
        if (featuredBtn && featuredInput && featuredBtn.dataset.bound !== '1') {
            featuredBtn.dataset.bound = '1';
            featuredBtn.addEventListener('click', () => featuredInput.click());
        }
        if (featuredInput && featuredInput.dataset.bound !== '1') {
            featuredInput.dataset.bound = '1';
            featuredInput.addEventListener('change', () => {
                const file = featuredInput.files?.[0];
                if (!file) return;
                if (featuredPath) featuredPath.value = '';
                if (featuredNote) {
                    featuredNote.textContent = `${file.name} selected from this device. Save the post to apply the new featured image.`;
                }
                if (featuredPreview) {
                    const previousObjectUrl = featuredPreview.dataset.objectUrl || '';
                    if (previousObjectUrl) URL.revokeObjectURL(previousObjectUrl);
                    const objectUrl = URL.createObjectURL(file);
                    featuredPreview.dataset.objectUrl = objectUrl;
                    featuredPreview.innerHTML = `<img src="${objectUrl}" alt="${this.escapeHtml(file.name)}">`;
                    featuredPreview.classList.add('has-image');
                }
            });
        }
        const fileInput = document.getElementById('blogAttachmentInput');
        const uploadBtn = document.getElementById('chooseBlogAttachmentBtn');
        const libraryBtn = document.getElementById('chooseBlogAttachmentLibraryBtn');
        const hiddenPath = document.getElementById('blogAttachmentPath');
        const note = document.getElementById('blogAttachmentNote');
        if (uploadBtn && fileInput && uploadBtn.dataset.bound !== '1') {
            uploadBtn.dataset.bound = '1';
            uploadBtn.addEventListener('click', () => fileInput.click());
        }
        if (fileInput && fileInput.dataset.bound !== '1') {
            fileInput.dataset.bound = '1';
            fileInput.addEventListener('change', () => {
                const file = fileInput.files?.[0];
                if (!file) return;
                if (hiddenPath) hiddenPath.value = '';
                if (note) note.textContent = `${file.name} selected from this device.`;
            });
        }
        if (libraryBtn && libraryBtn.dataset.bound !== '1') {
            libraryBtn.dataset.bound = '1';
            libraryBtn.addEventListener('click', () => {
                this.openMediaLibraryPicker({
                    title: 'Choose Blog Attachment',
                    onConfirm: (asset) => {
                        if (hiddenPath) hiddenPath.value = asset.file_path || '';
                        if (fileInput) fileInput.value = '';
                        if (note) {
                            note.textContent = `${asset.display_name || asset.original_name || asset.filename || 'File'} selected from the media library.`;
                        }
                        this.showSuccess('Attachment selected from the media library');
                    }
                });
            });
        }
        if (note && !note.textContent.trim() && post.attachment_path) {
            note.textContent = `Current: ${post.attachment_name || post.attachment_path.split('/').pop()}`;
        }
    };

    KomaginAdmin.prototype.saveBlogPost = async function(formData) {
        this.showLoading(true);
        try {
            const result = await fetch(`${this.apiBase}?action=blog_posts_save`, { method: 'POST', body: formData }).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'Blog post could not be saved');
            this.showSuccess(result.message || 'Blog post saved');
            this.hideModal('upgradeModal');
            await this.loadBlogPosts();
            this.loadDashboardData();
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.deleteBlogPost = async function(id) {
        this.showStyledConfirm({
            title: 'Delete Blog Post',
            message: 'Delete this blog post? This will remove it from the public website and the admin listing.',
            confirmText: 'Delete Post',
            confirmClass: 'btn-danger',
            onConfirm: async () => {
                this.hideModal('confirmModal');
                await this.runUpgradeAction(`blog_posts_delete&id=${encodeURIComponent(id)}`, null, 'blog-posts');
            }
        });
    };
})();

// COMMUNITY DROPDOWN MANAGER: expandable Building Stronger Communities cards with galleries.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__communityDropdownManager) return;
    KomaginAdmin.prototype.__communityDropdownManager = true;

    const oldLoadSectionData = KomaginAdmin.prototype.loadSectionData;
    KomaginAdmin.prototype.loadSectionData = function(section) {
        if (section === 'site-content') {
            this.loadSettings();
            this.loadCommunityCardsManager('communityCardsManager');
            return;
        }
        if (section === 'csr-items') {
            this.loadCommunityCardsManager('csrItemsContainer');
            return;
        }
        return oldLoadSectionData.call(this, section);
    };

    const oldInit = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInit.call(this);
        const bind = (id, handler) => {
            const btn = document.getElementById(id);
            if (btn && btn.dataset.bound !== '1') {
                btn.dataset.bound = '1';
                btn.addEventListener('click', handler);
            }
        };
        bind('addCommunityCardBtn', () => this.openCommunityCardForm(null, 'communityCardsManager'));
        bind('addCommunityCardBtnAlt', () => this.openCommunityCardForm(null, 'csrItemsContainer'));
    };

    KomaginAdmin.prototype.uploadSingleImageAsset = async function(file) {
        const allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!file || !allowedTypes.includes((file.type || '').toLowerCase())) {
            throw new Error('Only JPG, PNG, GIF, and WebP images are allowed');
        }
        if (file.size > 5 * 1024 * 1024) {
            throw new Error('Image must be 5MB or smaller');
        }
        const formData = new FormData();
        formData.append('image', file);
        const result = await this.readAdminJsonResponse(
            fetch(`${this.apiBase}?action=upload_image`, { method: 'POST', body: formData }),
            'Image upload failed'
        );
        if (!result.success) throw new Error(result.error || 'Image upload failed');
        return result;
    };

    KomaginAdmin.prototype.readAdminJsonResponse = async function(responsePromise, fallbackMessage = 'Request failed') {
        const response = await responsePromise;
        const payload = await response.text();
        let result = null;
        try {
            result = JSON.parse(payload);
        } catch (error) {
            const normalized = String(payload || '').trim();
            if (!response.ok || normalized.startsWith('<') || /Authentication required|login/i.test(normalized)) {
                throw new Error('Admin session expired. Please log in again.');
            }
            throw new Error(fallbackMessage);
        }
        if (!response.ok) throw new Error(result?.error || fallbackMessage);
        return result;
    };

    KomaginAdmin.prototype.loadCommunityCardsManager = async function(targetId = 'communityCardsManager') {
        const container = document.getElementById(targetId);
        if (!container) return;
        container.innerHTML = `<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><p>Loading community cards...</p></div>`;
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=csr_get_all`),
                'Community cards could not be loaded'
            );
            if (!result.success) throw new Error(result.error || 'Community cards could not be loaded');
            this._communityCards = result.data || [];
            const cards = this._communityCards;
            if (!cards.length) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-hands-helping"></i><p>No community cards yet. Add the first one to start building the dropdown section.</p></div>`;
                return;
            }
            container.innerHTML = `<div class="csr-card-list">${cards.map(card => {
                const bulletCount = Array.isArray(card.dropdown_bullets) ? card.dropdown_bullets.length : 0;
                const thumb = card.image
                    ? `<img class="csr-row-thumb" src="${this.resolveAdminAssetUrl(card.image)}" alt="${this.escapeHtml(card.title || 'Community image')}">`
                    : `<div class="csr-row-icon-box"><i class="fas ${this.escapeHtml(card.icon || 'fa-hands-helping')}"></i></div>`;
                const visBadge = card.is_active == 1
                    ? `<span class="csr-badge csr-badge-vis"><i class="fas fa-eye"></i> Visible</span>`
                    : `<span class="csr-badge csr-badge-hid"><i class="fas fa-eye-slash"></i> Hidden</span>`;
                return `<div class="csr-row-card">
                    ${thumb}
                    <div class="csr-row-info">
                        <div class="csr-row-top">
                            <span class="csr-row-title">${this.escapeHtml(card.title || 'Community Card')}</span>
                            ${visBadge}
                            <span class="csr-badge csr-badge-bullets"><i class="fas fa-list-ul"></i> ${bulletCount} bullet${bulletCount !== 1 ? 's' : ''}</span>
                            <span class="csr-badge csr-badge-order"><i class="fas fa-sort-numeric-down"></i> Order ${this.escapeHtml(String(card.sort_order ?? 0))}</span>
                        </div>
                        ${card.description ? `<div class="csr-row-desc">${this.escapeHtml(card.description)}</div>` : ''}
                        ${card.button_label ? `<div class="csr-row-btn-label"><i class="fas fa-hand-pointer"></i>Button: "${this.escapeHtml(card.button_label)}"</div>` : ''}
                    </div>
                    <div class="csr-row-actions">
                        ${this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: 'Edit community card', attrs: `onclick="komaginAdmin.openCommunityCardForm('${card.id}','${targetId}')"` })}
                        ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete community card', attrs: `onclick="komaginAdmin.deleteCommunityCard('${card.id}','${targetId}')"` })}
                    </div>
                </div>`;
            }).join('')}</div>`;
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><p>${this.escapeHtml(error.message)}</p></div>`;
        }
    };

    KomaginAdmin.prototype.openCommunityCardForm = function(id = null, targetId = 'communityCardsManager') {
        const card = id ? (this._communityCards || []).find(item => item.id === id) || {} : {};
        this._communityCardsTarget = targetId;
        document.getElementById('upgradeModalTitle').textContent = id ? 'Edit Community Card' : 'Add Community Card';
        document.getElementById('upgradeModalBody').innerHTML = `<form id="communityCardForm" class="product-form">
            <input type="hidden" name="id" value="${this.escapeHtml(card.id || '')}">
            <div class="form-grid">
                <div class="form-group"><label>Card Title *</label><input name="title" required value="${this.escapeHtml(card.title || '')}" placeholder="Community card title"></div>
                <div class="form-group"><label>Icon</label><input name="icon" value="${this.escapeHtml(card.icon || 'fa-hands-helping')}" placeholder="fa-hands-helping"></div>
                <div class="form-group"><label>Dropdown Button Label</label><input name="button_label" value="${this.escapeHtml(card.button_label || 'Explore More')}" placeholder="Explore More"></div>
                <div class="form-group"><label>Display Order</label><input type="number" name="sort_order" value="${this.escapeHtml(String(card.sort_order ?? 0))}"></div>
                <div class="form-group"><label>Visible</label><select name="is_active"><option value="1" ${(String(card.is_active ?? 1) === '1') ? 'selected' : ''}>Visible</option><option value="0" ${(String(card.is_active ?? 1) === '0') ? 'selected' : ''}>Hidden</option></select></div>
                <div class="form-group"></div>
                <div class="form-group" style="grid-column:1/-1;"><label>Card Summary</label><textarea name="description" rows="3" placeholder="Short summary shown before the dropdown opens">${this.escapeHtml(card.description || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1;"><label>Dropdown Header</label><input name="dropdown_header" value="${this.escapeHtml(card.dropdown_header || card.title || '')}" placeholder="Main title inside the dropdown"></div>
                <div class="form-group" style="grid-column:1/-1;"><label>Dropdown Subheader</label><textarea name="dropdown_subheader" rows="3" placeholder="Short supporting introduction inside the dropdown">${this.escapeHtml(card.dropdown_subheader || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1;"><label>Bullet Points</label><textarea name="dropdown_bullets" rows="7" placeholder="Enter one bullet point per line">${this.escapeHtml((Array.isArray(card.dropdown_bullets) ? card.dropdown_bullets : []).join('\n'))}</textarea><small>The public dropdown will show only a header, a subheader, and bullet points.</small></div>
                <div class="form-group" style="grid-column:1/-1;">
                    <label>Cover Image</label>
                    <div class="image-upload-container">
                        <div class="image-preview" id="communityCoverPreview">${card.image ? `<img src="${this.resolveAdminAssetUrl(card.image)}" alt="Cover image">` : '<i class="fas fa-image"></i><span>No image selected</span>'}</div>
                        <input type="file" id="communityCoverInput" accept="image/*" class="file-input">
                        <div class="image-upload-actions">
                            <button type="button" class="btn btn-outline" id="uploadCommunityCoverBtn"><i class="fas fa-upload"></i> Upload Image</button>
                        </div>
                    </div>
                    <small>The cover image appears on the main community card before the dropdown opens.</small>
                    <input type="hidden" name="image" id="communityCoverPath" value="${this.escapeHtml(card.image || '')}">
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="window.komaginAdmin && window.komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Community Card</button>
            </div>
        </form>`;

        const coverBtn = document.getElementById('uploadCommunityCoverBtn');
        const coverInput = document.getElementById('communityCoverInput');
        if (coverBtn && coverInput) coverBtn.addEventListener('click', () => coverInput.click());
        if (coverInput) {
            coverInput.addEventListener('change', async () => {
                const file = coverInput.files && coverInput.files[0];
                if (!file) return;
                this.showLoading(true);
                try {
                    const result = await this.uploadSingleImageAsset(file);
                    const hidden = document.getElementById('communityCoverPath');
                    if (hidden) hidden.value = result.file_path;
                    const preview = document.getElementById('communityCoverPreview');
                    if (preview) preview.innerHTML = `<img src="${result.file_url}" alt="Cover image">`;
                    this.showSuccess('Cover image uploaded');
                } catch (error) {
                    this.showError(error.message);
                } finally {
                    this.showLoading(false);
                }
            });
        }
        document.getElementById('communityCardForm').addEventListener('submit', event => {
            event.preventDefault();
            this.saveCommunityCard(new FormData(event.target));
        });
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.saveCommunityCard = async function(formData) {
        const payload = Object.fromEntries(formData.entries());
        payload.dropdown_bullets = String(payload.dropdown_bullets || '')
            .split(/\r?\n/)
            .map(line => line.trim())
            .filter(Boolean);
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=csr_save`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                }),
                'Community card could not be saved'
            );
            if (!result.success) throw new Error(result.error || 'Community card could not be saved');
            this.showSuccess(result.message || 'Community card saved');
            this.hideModal('upgradeModal');
            await this.loadCommunityCardsManager(this._communityCardsTarget || 'communityCardsManager');
            if (document.getElementById('communityCardsManager') && (this._communityCardsTarget || '') !== 'communityCardsManager') {
                this.loadCommunityCardsManager('communityCardsManager');
            }
            if (document.getElementById('csrItemsContainer') && (this._communityCardsTarget || '') !== 'csrItemsContainer') {
                this.loadCommunityCardsManager('csrItemsContainer');
            }
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.deleteCommunityCard = async function(id, targetId = 'communityCardsManager') {
        this.showStyledConfirm({
            title: 'Delete Community Card',
            message: 'Delete this community card? This will remove it from the Building Stronger Communities section.',
            confirmText: 'Delete Card',
            confirmClass: 'btn-danger',
            onConfirm: async () => {
                this.hideModal('confirmModal');
                await this.runUpgradeAction(`csr_delete&id=${encodeURIComponent(id)}`, null, null);
                await this.loadCommunityCardsManager(targetId);
                if (document.getElementById('communityCardsManager') && targetId !== 'communityCardsManager') {
                    this.loadCommunityCardsManager('communityCardsManager');
                }
                if (document.getElementById('csrItemsContainer') && targetId !== 'csrItemsContainer') {
                    this.loadCommunityCardsManager('csrItemsContainer');
                }
            }
        });
    };
})();

// WEBSITE CAREERS MERGE: jobs and applications now live inside the single website admin panel.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__webCareersMerge) return;
    KomaginAdmin.prototype.__webCareersMerge = true;

    const oldLoadSectionData = KomaginAdmin.prototype.loadSectionData;
    KomaginAdmin.prototype.loadSectionData = function(section) {
        if (section === 'jobs') return this.loadJobs();
        if (section === 'applications') return this.loadApplications();
        return oldLoadSectionData.call(this, section);
    };

    const oldInit = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInit.call(this);

        const bindClick = (id, handler) => {
            const btn = document.getElementById(id);
            if (btn && btn.dataset.bound !== '1') {
                btn.dataset.bound = '1';
                btn.addEventListener('click', handler);
            }
        };

        bindClick('addJobBtn', () => this.openJobForm());
        bindClick('quickAddJob', () => this.openJobForm());

        document.querySelectorAll('.app-filter-btn').forEach(btn => {
            if (btn.dataset.bound === '1') return;
            btn.dataset.bound = '1';
            btn.addEventListener('click', () => {
                this.jobAppFilter = btn.dataset.appFilter || 'all';
                document.querySelectorAll('.app-filter-btn').forEach(item => item.classList.toggle('active', item === btn));
                this.loadApplications();
            });
        });
    };

    KomaginAdmin.prototype.loadJobs = async function() {
        const container = document.getElementById('jobsTableBody');
        if (!container) return;
        container.innerHTML = `<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><span>Loading vacancies...</span></div>`;
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=jobs_list`, { credentials: 'same-origin' }),
                'Failed to load job vacancies'
            );
            if (!result.success) throw new Error(result.error || 'Failed to load job vacancies');
            const rows = result.data || [];
            this._jobsData = rows;
            if (!rows.length) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-briefcase"></i><span>No vacancies posted yet.</span></div>`;
                return;
            }
            const statusBadgeClass = (status) => {
                if (status === 'published') return 'job-badge-pub';
                if (status === 'draft') return 'job-badge-draft';
                if (status === 'closed') return 'job-badge-closed';
                return 'job-badge-arch';
            };
            container.innerHTML = `<div class="job-card-list">${rows.map(job => {
                return `<div class="job-row-card">
                    <div class="job-row-icon"><i class="fas fa-briefcase"></i></div>
                    <div class="job-row-info">
                        <div class="job-row-top">
                            <span class="job-row-title">${this.escapeHtml(job.title || '-')}</span>
                            <span class="job-badge ${statusBadgeClass(job.status || 'draft')}">${this.escapeHtml(this.label(job.status || 'draft'))}</span>
                            <span class="job-badge job-badge-type">${this.escapeHtml(this.label(job.type || 'full_time'))}</span>
                            ${job.applications_count > 0 ? `<span class="job-badge job-badge-apps"><i class="fas fa-file-signature"></i> ${job.applications_count} app${job.applications_count !== 1 ? 's' : ''}</span>` : ''}
                        </div>
                        <div class="job-row-meta">
                            ${job.department ? `<span><i class="fas fa-building"></i>${this.escapeHtml(job.department)}</span>` : ''}
                            ${job.location ? `<span><i class="fas fa-map-marker-alt"></i>${this.escapeHtml(job.location)}</span>` : ''}
                            <span><i class="fas fa-calendar"></i>${job.closing_date ? this.formatDate(job.closing_date) : 'Open until filled'}</span>
                        </div>
                    </div>
                    <div class="job-row-actions">
                        ${this.iconButton({ variant: 'btn-outline', icon: 'fa-pen-to-square', label: 'Edit vacancy', attrs: `onclick="komaginAdmin.openJobForm('${job.id}')"` })}
                        ${this.iconButton({ variant: 'btn-danger', icon: 'fa-trash', label: 'Delete vacancy', attrs: `onclick="komaginAdmin.deleteJob('${job.id}')"` })}
                    </div>
                </div>`;
            }).join('')}</div>`;
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><span>${this.escapeHtml(error.message)}</span></div>`;
        }
    };

    KomaginAdmin.prototype.openJobForm = function(id = null) {
        const job = id ? (this._jobsData || []).find(item => item.id === id) || {} : {};
        document.getElementById('upgradeModalTitle').textContent = id ? 'Edit Vacancy' : 'Post Vacancy';
        document.getElementById('upgradeModalBody').innerHTML = `<form id="jobForm">
            <input type="hidden" name="id" value="${this.escapeHtml(job.id || '')}">
            <div class="form-grid">
                <div class="form-group"><label>Job Title *</label><input name="title" required value="${this.escapeHtml(job.title || '')}" placeholder="e.g. Site Engineer"></div>
                <div class="form-group"><label>Department</label><input name="department" value="${this.escapeHtml(job.department || '')}" placeholder="Engineering, Operations, Commercial"></div>
                <div class="form-group"><label>Location</label><input name="location" value="${this.escapeHtml(job.location || '')}" placeholder="Port Moresby, PNG"></div>
                <div class="form-group"><label>Employment Type</label><select name="type">
                    ${['full_time','part_time','contract','casual','internship'].map(type => `<option value="${type}" ${String(job.type || 'full_time') === type ? 'selected' : ''}>${this.label(type)}</option>`).join('')}
                </select></div>
                <div class="form-group"><label>Closing Date</label><input type="date" name="closing_date" value="${this.escapeHtml(job.closing_date || '')}"></div>
                <div class="form-group"><label>Status</label><select name="status">
                    ${['draft','published','closed'].map(status => `<option value="${status}" ${String(job.status || 'draft') === status ? 'selected' : ''}>${this.label(status)}</option>`).join('')}
                </select></div>
                <div class="form-group" style="grid-column:1/-1"><label>Role Summary *</label><textarea name="description" rows="5" required placeholder="Describe the vacancy shown on the public website.">${this.escapeHtml(job.description || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label>Requirements</label><textarea name="requirements" rows="5" placeholder="Qualifications, certifications, and experience requirements.">${this.escapeHtml(job.requirements || '')}</textarea></div>
                <div class="form-group"><label>Salary Range</label><input name="salary_range" value="${this.escapeHtml(job.salary_range || '')}" placeholder="Optional, e.g. PGK 3,500 - 4,500 monthly"><label class="job-salary-toggle"><input type="checkbox" name="show_salary_range" value="1" ${Number(job.show_salary_range || 0) === 1 ? 'checked' : ''}><span>Publish salary range on public careers page</span></label></div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Vacancy</button>
            </div>
        </form>`;
        document.getElementById('jobForm').addEventListener('submit', event => {
            event.preventDefault();
            this.saveJob(new FormData(event.target));
        });
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.saveJob = async function(formData) {
        this.showLoading(true);
        try {
            const payload = Object.fromEntries(formData.entries());
            payload.show_salary_range = formData.get('show_salary_range') ? 1 : 0;
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=jobs_save`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(payload)
                }),
                'Vacancy could not be saved'
            );
            if (!result.success) throw new Error(result.error || 'Vacancy could not be saved');
            this.showSuccess(result.message || 'Vacancy saved');
            this.hideModal('upgradeModal');
            await this.loadJobs();
            this.loadDashboardData();
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.deleteJob = async function(id) {
        this.showStyledConfirm({
            title: 'Delete Vacancy',
            message: 'Delete this vacancy? This will remove it from the public careers page and the admin listing.',
            confirmText: 'Delete Vacancy',
            confirmClass: 'btn-danger',
            onConfirm: async () => {
                this.hideModal('confirmModal');
                this.showLoading(true);
                try {
                    const result = await this.readAdminJsonResponse(
                        fetch(`${this.apiBase}?action=jobs_delete&id=${encodeURIComponent(id)}`, { credentials: 'same-origin' }),
                        'Vacancy could not be deleted'
                    );
                    if (!result.success) throw new Error(result.error || 'Vacancy could not be deleted');
                    this.showSuccess(result.message || 'Vacancy deleted');
                    await this.loadJobs();
                    this.loadDashboardData();
                } catch (error) {
                    this.showError(error.message);
                } finally {
                    this.showLoading(false);
                }
            }
        });
    };

    KomaginAdmin.prototype.toggleJobStatus = async function(id) {
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=jobs_toggle_status&id=${encodeURIComponent(id)}`, { credentials: 'same-origin' }),
                'Vacancy status could not be updated'
            );
            if (!result.success) throw new Error(result.error || 'Vacancy status could not be updated');
            this.showSuccess(result.message || 'Vacancy status updated');
            await this.loadJobs();
            this.loadDashboardData();
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.loadApplications = async function() {
        const container = document.getElementById('applicationsTableBody');
        if (!container) return;
        const filter = this.jobAppFilter || 'all';
        container.innerHTML = `<div class="empty-state"><i class="fas fa-spinner fa-spin"></i><span>Loading applications...</span></div>`;
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=hr_get_applications&status=${encodeURIComponent(filter)}`),
                'Failed to load job applications'
            );
            if (!result.success) throw new Error(result.error || 'Failed to load applications');
            const rows = result.data || [];
            this._jobApplications = rows;
            if (!rows.length) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-file-signature"></i><span>No applications in this stage.</span></div>`;
                return;
            }
            const appStatusBadge = (status) => {
                const map = { received: 'app-badge-recv', shortlisted: 'app-badge-short', interview: 'app-badge-int', hired: 'app-badge-hired', rejected: 'app-badge-rej', withdrawn: 'app-badge-withdrawn' };
                return map[status] || 'app-badge-recv';
            };
            container.innerHTML = `<div class="app-card-list">${rows.map(app => {
                const bundleUrl = app.bundle_url || '';
                const initial = (app.applicant_name || '?').charAt(0).toUpperCase();
                const canInvite = ['shortlisted', 'interview'].includes(String(app.status || '').toLowerCase());
                return `<div class="app-row-card">
                    <div class="app-row-avatar">${initial}</div>
                    <div class="app-row-info">
                        <div class="app-row-top">
                            <span class="app-row-name">${this.escapeHtml(app.applicant_name || '-')}</span>
                            <span class="app-badge ${appStatusBadge(app.status || 'received')}">${this.escapeHtml(this.label(app.status || 'received'))}</span>
                        </div>
                        <div class="app-row-job"><i class="fas fa-briefcase"></i>${this.escapeHtml(app.job_title || '-')}</div>
                        <div class="app-row-meta">
                            ${app.email ? `<span><i class="fas fa-envelope"></i>${this.escapeHtml(app.email)}</span>` : ''}
                            ${app.phone ? `<span><i class="fas fa-phone"></i>${this.escapeHtml(app.phone)}</span>` : ''}
                            ${app.created_at ? `<span><i class="fas fa-calendar"></i>${this.formatDate(app.created_at)}</span>` : ''}
                            ${bundleUrl ? `<a class="app-cv-link" href="${this.escapeHtml(bundleUrl)}" target="_blank"><i class="fas fa-file-zipper"></i> Open ZIP</a>` : ''}
                            ${Number(app.documents_count || 0) > 0 ? `<span><i class="fas fa-folder-open"></i>${Number(app.documents_count || 0)} doc${Number(app.documents_count || 0) === 1 ? '' : 's'}</span>` : ''}
                        </div>
                    </div>
                    <div class="app-row-actions">
                        <button class="btn btn-sm btn-outline app-action-btn" onclick="komaginAdmin.openApplicationDetail('${app.id}')"><i class="fas fa-address-card"></i> Details</button>
                        <button class="btn btn-sm btn-success app-action-btn" onclick="komaginAdmin.updateApplicationStatus('${app.id}','shortlisted')"><i class="fas fa-star"></i> Shortlist</button>
                        <button class="btn btn-sm btn-outline app-action-btn" onclick="komaginAdmin.updateApplicationStatus('${app.id}','interview')"><i class="fas fa-calendar-check"></i> Interview</button>
                        <button class="btn btn-sm btn-primary app-action-btn" onclick="komaginAdmin.updateApplicationStatus('${app.id}','hired')"><i class="fas fa-user-check"></i> Hire</button>
                        <button class="btn btn-sm btn-danger app-action-btn" onclick="komaginAdmin.updateApplicationStatus('${app.id}','rejected')"><i class="fas fa-xmark"></i> Reject</button>
                        ${canInvite ? `<button class="btn btn-sm btn-outline app-action-btn" onclick="window.open('${this.apiBase}?action=hr_generate_interview_invite&id=${app.id}','_blank')"><i class="fas fa-envelope"></i> Invite</button>` : ''}
                    </div>
                </div>`;
            }).join('')}</div>`;
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><span>${this.escapeHtml(error.message)}</span></div>`;
        }
    };

    KomaginAdmin.prototype.updateApplicationStatus = async function(id, status) {
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=hr_update_application_status`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ id, status })
                }),
                'Application could not be updated'
            );
            if (!result.success) throw new Error(result.error || 'Application could not be updated');
            this.showSuccess(result.message || 'Application updated');
            await this.loadApplications();
            this.loadDashboardData();
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.openApplicationDetail = async function(id) {
        this.showLoading(true);
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=hr_get_application_detail&id=${encodeURIComponent(id)}`),
                'Applicant details could not be loaded'
            );
            if (!result.success || !result.data) throw new Error(result.error || 'Applicant details could not be loaded');
            const app = result.data;
            const statusClassMap = {
                received: 'app-badge-recv',
                shortlisted: 'app-badge-short',
                interview: 'app-badge-int',
                hired: 'app-badge-hired',
                rejected: 'app-badge-rej',
                withdrawn: 'app-badge-withdrawn'
            };
            const documents = Array.isArray(app.documents) ? app.documents : [];
            const groupedDocuments = documents.reduce((groups, doc) => {
                const key = doc.folder || 'Root Documents';
                if (!groups[key]) groups[key] = [];
                groups[key].push(doc);
                return groups;
            }, {});
            const documentMarkup = documents.length
                ? Object.entries(groupedDocuments).map(([folder, docs]) => `
                    <div class="application-doc-group">
                        <h4>${this.escapeHtml(folder === 'Root Documents' ? folder : folder.replace(/\//g, ' / '))}</h4>
                        <div class="application-doc-list">
                            ${docs.map(doc => `<a class="application-doc-link" href="${this.escapeHtml(doc.url || '#')}" target="_blank"><i class="fas fa-file-lines"></i><span>${this.escapeHtml(doc.name || 'Document')}</span></a>`).join('')}
                        </div>
                    </div>
                `).join('')
                : `<div class="empty-state compact"><i class="fas fa-file-zipper"></i><span>No extracted documents are available for this applicant yet.</span></div>`;
            document.getElementById('upgradeModalTitle').textContent = 'Applicant Details';
            document.getElementById('upgradeModalBody').innerHTML = `
                <div class="application-detail-layout">
                    <section class="application-detail-hero">
                        <div class="application-detail-avatar">${this.escapeHtml((app.applicant_name || '?').charAt(0).toUpperCase())}</div>
                        <div class="application-detail-heading">
                            <h3>${this.escapeHtml(app.applicant_name || 'Applicant')}</h3>
                            <div class="application-detail-badges">
                                <span class="app-badge ${statusClassMap[String(app.status || 'received').toLowerCase()] || 'app-badge-recv'}">${this.escapeHtml(this.label(app.status || 'received'))}</span>
                                ${app.job_title ? `<span class="app-badge app-badge-job"><i class="fas fa-briefcase"></i>${this.escapeHtml(app.job_title)}</span>` : ''}
                            </div>
                        </div>
                    </section>
                    <section class="application-detail-grid">
                        <div class="application-detail-card">
                            <h4>Applicant Information</h4>
                            <div class="application-detail-meta">
                                ${app.email ? `<p><strong>Email:</strong> <a href="mailto:${this.escapeHtml(app.email)}">${this.escapeHtml(app.email)}</a></p>` : ''}
                                ${app.phone ? `<p><strong>Phone:</strong> <a href="tel:${this.escapeHtml(app.phone)}">${this.escapeHtml(app.phone)}</a></p>` : ''}
                                ${app.created_at ? `<p><strong>Submitted:</strong> ${this.escapeHtml(this.formatDate(app.created_at))}</p>` : ''}
                                ${app.reviewed_by ? `<p><strong>Reviewed By:</strong> ${this.escapeHtml(app.reviewed_by)}</p>` : ''}
                                ${app.reviewed_at ? `<p><strong>Reviewed At:</strong> ${this.escapeHtml(this.formatDate(app.reviewed_at))}</p>` : ''}
                                ${app.job_department ? `<p><strong>Department:</strong> ${this.escapeHtml(app.job_department)}</p>` : ''}
                                ${app.job_location ? `<p><strong>Location:</strong> ${this.escapeHtml(app.job_location)}</p>` : ''}
                            </div>
                        </div>
                        <div class="application-detail-card">
                            <h4>Cover Note</h4>
                            <p class="application-detail-note">${this.escapeHtml(app.cover_note || 'No cover note submitted.')}</p>
                        </div>
                    </section>
                    <section class="application-detail-card">
                        <div class="application-detail-card-head">
                            <h4>Submitted Document Bundle</h4>
                            ${app.bundle_url ? `<a class="btn btn-outline btn-sm" href="${this.escapeHtml(app.bundle_url)}" target="_blank"><i class="fas fa-download"></i> Download ZIP</a>` : ''}
                        </div>
                        <p class="application-detail-note">${this.escapeHtml(app.bundle_name || 'No ZIP package uploaded.')}</p>
                        <small>${Number(app.documents_count || 0)} extracted document${Number(app.documents_count || 0) === 1 ? '' : 's'} available for review.</small>
                    </section>
                    <section class="application-detail-card">
                        <h4>Organized Documents</h4>
                        ${documentMarkup}
                    </section>
                    <div class="form-actions">
                        <button type="button" class="btn btn-secondary" onclick="komaginAdmin.hideModal('upgradeModal')">Close</button>
                    </div>
                </div>
            `;
            this.showModal('upgradeModal');
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.loadRecentApplications = async function() {
        const container = document.getElementById('recentApplications');
        if (!container) return;
        try {
            const result = await fetch(`${this.apiBase}?action=hr_get_applications&status=all`).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'Recent applications could not be loaded');
            const rows = (result.data || []).slice(0, 5);
            if (!rows.length) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-file-signature"></i><p>No applications received yet.</p></div>`;
                return;
            }
            container.innerHTML = rows.map(app => `<div class="activity-item">
                <div class="activity-content">
                    <h4>${this.escapeHtml(app.applicant_name || 'Applicant')}</h4>
                    <p>${this.escapeHtml(app.job_title || 'Vacancy')} ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ <span class="status-badge ${this.getApplicationStatusClass(app.status || 'received')}">${this.escapeHtml(this.label(app.status || 'received'))}</span></p>
                    <small>${app.created_at ? this.formatDate(app.created_at) : ''}</small>
                </div>
            </div>`).join('');
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><p>${this.escapeHtml(error.message)}</p></div>`;
        }
    };

    KomaginAdmin.prototype.getApplicationStatusClass = function(status) {
        const value = String(status || '').toLowerCase();
        if (value === 'received') return 'status-pending';
        if (value === 'shortlisted') return 'status-approved';
        if (value === 'interview') return 'status-in_progress';
        if (value === 'hired') return 'status-active';
        if (value === 'rejected') return 'status-rejected';
        return `status-${value}`;
    };
})();

// KOMAGIN BRANCH MANAGER ACCOUNT: branch login is managed from Branch Registry.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__branchUsersPatch) return;
    KomaginAdmin.prototype.__branchUsersPatch = true;

    const oldGetUpgradeConfig = KomaginAdmin.prototype.getUpgradeConfig;
    KomaginAdmin.prototype.getUpgradeConfig = function() {
        const cfg = oldGetUpgradeConfig.call(this);
        if (cfg['branches-list']) {
            cfg['branches-list'].actions = (r) => `<button class="btn btn-sm btn-outline icon-btn" title="Edit" onclick="komaginAdmin.openUpgradeForm('branches-list','${r.id}')"><i class="fas fa-pen-to-square"></i></button><button class="btn btn-sm btn-primary icon-btn" title="Set Password" onclick="komaginAdmin.resetBranchManagerPassword('${r.id}','${this.escapeHtml(r.manager_username || '')}')"><i class="fas fa-key"></i></button><button class="btn btn-sm btn-outline icon-btn" title="Provision" onclick="komaginAdmin.runUpgradeAction('branches_provision_template&branch_id=${r.id}',null,'branches-list')"><i class="fas fa-server"></i></button>`;
        }
        return cfg;
    };

    const oldOpenUpgradeForm = KomaginAdmin.prototype.openUpgradeForm;
    KomaginAdmin.prototype.openUpgradeForm = function(section, id = null) {
        oldOpenUpgradeForm.call(this, section, id);
    };

    KomaginAdmin.prototype.resetBranchManagerPassword = async function(branchId, currentUsername = '') {
        const password = prompt('Enter the new branch manager password. It must be at least 6 characters.');
        if (!password) return;
        if (password.length < 6) {
            this.showError('Branch password must be at least 6 characters');
            return;
        }
        await this.runUpgradeAction('branch_manager_password_set', {
            branch_id: branchId,
            manager_username: currentUsername || '',
            manager_password: password
        }, 'branches-list');
    };

})();

// PLANT HIRE CMS: public hire page settings plus equipment listing management.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__plantHireCms) return;
    KomaginAdmin.prototype.__plantHireCms = true;

    const oldLoadSectionData = KomaginAdmin.prototype.loadSectionData;
    KomaginAdmin.prototype.loadSectionData = function(section) {
        if (section === 'plant-hire-admin') return this.loadPlantHireAdmin();
        return oldLoadSectionData.call(this, section);
    };

    const oldInit = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInit.call(this);
        const addBtn = document.getElementById('addHireItemBtn');
        if (addBtn && addBtn.dataset.bound !== '1') {
            addBtn.dataset.bound = '1';
            addBtn.addEventListener('click', () => this.openHireItemForm());
        }
        const form = document.getElementById('plantHirePageForm');
        if (form && form.dataset.bound !== '1') {
            form.dataset.bound = '1';
            form.addEventListener('submit', event => {
                event.preventDefault();
                this.savePlantHirePage();
            });
        }
        const uploadBtn = document.getElementById('uploadHirePageImageBtn');
        const uploadInput = document.getElementById('hirePageImageInput');
        if (uploadBtn && uploadInput && uploadBtn.dataset.bound !== '1') {
            uploadBtn.dataset.bound = '1';
            uploadBtn.addEventListener('click', () => uploadInput.click());
            uploadInput.addEventListener('change', async () => {
                const file = uploadInput.files && uploadInput.files[0];
                if (!file) return;
                this.showLoading(true);
                try {
                    const result = await this.uploadSingleImageAsset(file);
                    document.getElementById('hirePageImagePath').value = result.file_path || '';
                    this.updateImagePreview('hirePage', result.file_url, 'Hire page hero image');
                    this.showSuccess('Hire page hero image uploaded');
                } catch (error) {
                    this.showError(error.message);
                } finally {
                    this.showLoading(false);
                }
            });
        }
    };

    KomaginAdmin.prototype.loadPlantHireAdmin = async function() {
        await this.loadSettings();
        this.displayPlantHireSettings();
        await this.loadUpgradeSection('plant-hire-items');
        this.bindPlantHireCardActions();
    };

    KomaginAdmin.prototype.bindPlantHireCardActions = function() {
        const container = document.getElementById('plantHireItemsContainer');
        if (!container || container.dataset.boundHireActions === '1') return;
        container.dataset.boundHireActions = '1';
        container.addEventListener('click', event => {
            const actionButton = event.target.closest('[data-hire-action]');
            if (!actionButton) return;
            event.preventDefault();
            event.stopPropagation();
            const hireId = actionButton.getAttribute('data-hire-id') || '';
            const action = actionButton.getAttribute('data-hire-action') || '';
            if (!hireId) return;
            if (action === 'edit') this.openHireItemForm(hireId);
            if (action === 'delete') this.deleteHireItem(hireId);
        });
    };

    KomaginAdmin.prototype.renderHireItemPreviewMarkup = function(imagePath) {
        const normalized = String(imagePath || '').trim();
        if (!normalized) {
            return '<i class="fas fa-image"></i><span>No image uploaded</span>';
        }
        return `<img src="${this.escapeHtml(this.resolveAdminAssetUrl(normalized))}" alt="Hire equipment image">`;
    };

    KomaginAdmin.prototype.openHireItemForm = async function(id = null) {
        const cacheKey = '_plant-hire-itemsData';
        if (!Array.isArray(this[cacheKey]) || !this[cacheKey].length) {
            await this.loadUpgradeSection('plant-hire-items');
        }
        const item = id ? ((this[cacheKey] || []).find(row => String(row.id || '') === String(id)) || null) : null;
        if (id && !item) {
            this.showError('That hire item could not be loaded. Please refresh the hire list and try again.');
            return;
        }
        const data = item || {};
        document.getElementById('upgradeModalTitle').textContent = id ? 'Edit Hire Item' : 'Add Hire Item';
        document.getElementById('upgradeModalBody').innerHTML = `<form id="hireItemForm" class="product-form">
            <input type="hidden" name="id" value="${this.escapeHtml(data.id || '')}">
            <div class="form-grid">
                <div class="form-group"><label>Equipment Name *</label><input type="text" name="name" required value="${this.escapeHtml(data.name || '')}" placeholder="e.g. Excavator 20T"></div>
                <div class="form-group"><label>Category</label><select name="category">
                    ${['excavators','graders','rollers','loaders','dump_trucks','bulldozers','cranes','water_carts','compactors','survey_support','survey_equipment','generators','support_vehicles','other'].map(option => `<option value="${option}" ${String(data.category || 'other') === option ? 'selected' : ''}>${this.label(option)}</option>`).join('')}
                </select></div>
                <div class="form-group"><label>Availability</label><select name="availability_status">
                    ${['available','on_request','limited','booked'].map(option => `<option value="${option}" ${String(data.availability_status || 'available') === option ? 'selected' : ''}>${this.label(option)}</option>`).join('')}
                </select></div>
                <div class="form-group"><label>Location</label><input type="text" name="location" value="${this.escapeHtml(data.location || '')}" placeholder="e.g. Port Moresby Yard"></div>
                <div class="form-group"><label>Operator Option</label><select name="operator_option">
                    ${['included','optional','not_included'].map(option => `<option value="${option}" ${String(data.operator_option || 'optional') === option ? 'selected' : ''}>${this.label(option)}</option>`).join('')}
                </select></div>
                <div class="form-group"><label>Delivery Option</label><select name="delivery_option">
                    ${['available','on_request','pickup_only'].map(option => `<option value="${option}" ${String(data.delivery_option || 'available') === option ? 'selected' : ''}>${this.label(option)}</option>`).join('')}
                </select></div>
                <div class="form-group" style="grid-column:1/-1"><label>Short Description *</label><textarea name="short_description" rows="4" required placeholder="Short public-facing summary for this hire item.">${this.escapeHtml(data.short_description || '')}</textarea></div>
                <div class="form-group" style="grid-column:1/-1"><label>Specifications</label><textarea name="specifications" rows="4" placeholder="Key specifications, capacity, attachments, or usage notes.">${this.escapeHtml(data.specifications || '')}</textarea></div>
                <div class="form-group"><label>Rate / Hire Note</label><input type="text" name="rate_note" value="${this.escapeHtml(data.rate_note || '')}" placeholder="e.g. Rate on request"></div>
                <div class="form-group"><label>Tags</label><input type="text" name="tags" value="${this.escapeHtml(data.tags || '')}" placeholder="Comma-separated tags"></div>
                <div class="form-group"><label>Featured</label><select name="featured">
                    <option value="1" ${String(data.featured || 0) === '1' ? 'selected' : ''}>Yes</option>
                    <option value="0" ${String(data.featured || 0) === '0' ? 'selected' : ''}>No</option>
                </select></div>
                <div class="form-group"><label>Display Order</label><input type="number" name="sort_order" value="${this.escapeHtml(String(data.sort_order ?? 0))}" min="0"></div>
                <div class="form-group"><label>Visible on Website</label><select name="is_active">
                    <option value="1" ${String(data.is_active ?? 1) === '1' ? 'selected' : ''}>Yes</option>
                    <option value="0" ${String(data.is_active ?? 1) === '0' ? 'selected' : ''}>No</option>
                </select></div>
                <div class="form-group" style="grid-column:1/-1">
                    <label>Equipment Image</label>
                    <div class="image-upload-container">
                        <div class="image-preview ${data.image ? 'has-image' : ''}" id="hireItemImagePreview">${this.renderHireItemPreviewMarkup(data.image || '')}</div>
                        <input type="hidden" name="image" id="hireItemImagePath" value="${this.escapeHtml(data.image || '')}">
                        <input type="file" id="hireItemImageInput" accept="image/*" class="file-input">
                        <div class="image-upload-actions">
                            <button type="button" class="btn btn-outline" id="uploadHireItemImageBtn">Upload Image</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-secondary" onclick="komaginAdmin.hideModal('upgradeModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Save Hire Item</button>
            </div>
        </form>`;
        const form = document.getElementById('hireItemForm');
        const uploadBtn = document.getElementById('uploadHireItemImageBtn');
        const uploadInput = document.getElementById('hireItemImageInput');
        const hiddenPath = document.getElementById('hireItemImagePath');
        const preview = document.getElementById('hireItemImagePreview');
        if (uploadBtn && uploadInput) {
            uploadBtn.addEventListener('click', () => uploadInput.click());
            uploadInput.addEventListener('change', async () => {
                const file = uploadInput.files && uploadInput.files[0];
                if (!file) return;
                this.showLoading(true);
                try {
                    const result = await this.uploadSingleImageAsset(file);
                    if (hiddenPath) hiddenPath.value = result.file_path || '';
                    if (preview) {
                        preview.classList.add('has-image');
                        preview.innerHTML = `<img src="${this.escapeHtml(result.file_url || this.resolveAdminAssetUrl(result.file_path || ''))}" alt="Hire equipment image">`;
                    }
                    this.showSuccess('Hire item image uploaded');
                } catch (error) {
                    this.showError(error.message);
                } finally {
                    this.showLoading(false);
                }
            });
        }
        if (form) {
            form.addEventListener('submit', event => {
                event.preventDefault();
                this.saveHireItemForm(new FormData(form));
            });
        }
        this.showModal('upgradeModal');
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
        this.enhanceIconInputs(document.getElementById('upgradeModal'));
    };

    KomaginAdmin.prototype.saveHireItemForm = async function(formData) {
        const payload = Object.fromEntries(formData.entries());
        this.showLoading(true);
        try {
            const result = await fetch(`${this.apiBase}?action=hire_item_save`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'Hire item could not be saved');
            this.showSuccess(result.message || 'Hire item saved');
            this.hideModal('upgradeModal');
            await this.loadPlantHireAdmin();
            this.loadDashboardData();
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.displayPlantHireSettings = function() {
        const map = {
            hirePageBadge: 'hire_page_badge',
            hirePageTitle: 'hire_page_title',
            hirePageSubtitle: 'hire_page_subtitle',
            hirePageIntro: 'hire_page_intro',
            hirePagePhone: 'hire_page_contact_phone',
            hirePageEmail: 'hire_page_contact_email',
            hirePageCtaLabel: 'hire_page_cta_label',
            hirePageCtaTarget: 'hire_page_cta_target'
        };
        Object.entries(map).forEach(([id, key]) => {
            const el = document.getElementById(id);
            if (el) el.value = this.settings[key] || '';
        });
        const imagePath = document.getElementById('hirePageImagePath');
        if (imagePath) imagePath.value = this.settings.hire_page_background_image || '';
        if (this.settings.hire_page_background_image) {
            this.updateImagePreview('hirePage', this.resolveAdminAssetUrl(this.settings.hire_page_background_image), 'Hire page hero image');
        } else {
            this.resetImagePreview('hirePage');
        }
    };

    KomaginAdmin.prototype.savePlantHirePage = async function() {
        const payload = {
            hire_page_badge: document.getElementById('hirePageBadge')?.value.trim() || '',
            hire_page_title: document.getElementById('hirePageTitle')?.value.trim() || '',
            hire_page_subtitle: document.getElementById('hirePageSubtitle')?.value.trim() || '',
            hire_page_intro: document.getElementById('hirePageIntro')?.value.trim() || '',
            hire_page_contact_phone: document.getElementById('hirePagePhone')?.value.trim() || '',
            hire_page_contact_email: document.getElementById('hirePageEmail')?.value.trim() || '',
            hire_page_cta_label: document.getElementById('hirePageCtaLabel')?.value.trim() || '',
            hire_page_cta_target: document.getElementById('hirePageCtaTarget')?.value.trim() || 'contact',
            hire_page_background_image: document.getElementById('hirePageImagePath')?.value.trim() || 'images/hero-bg.jpeg'
        };
        this.showLoading(true);
        try {
            const result = await fetch(`${this.apiBase}?action=update_settings`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            }).then(r => r.json());
            if (!result.success) throw new Error(result.error || 'Hire page settings could not be saved');
            this.settings = { ...this.settings, ...payload };
            this.showSuccess('Plant hire page saved');
        } catch (error) {
            this.showError(error.message);
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.editHireItem = async function(id) {
        await this.openHireItemForm(id);
    };

    KomaginAdmin.prototype.deleteHireItem = async function(id) {
        if (!id) return;
        this.showStyledConfirm({
            title: 'Delete Hire Item',
            message: 'Are you sure you want to delete this plant or equipment hire item? This action cannot be undone.',
            confirmText: 'Delete Item',
            confirmClass: 'btn-danger',
            onConfirm: async () => {
                this.hideModal('confirmModal');
                await this.runUpgradeAction(`hire_item_delete&id=${encodeURIComponent(id)}`, null, 'plant-hire-admin');
            }
        });
    };
})();

// DASHBOARD CHARTS: responsive SVG/HTML graphs for the web admin overview.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__dashboardCharts) return;
    KomaginAdmin.prototype.__dashboardCharts = true;

    const palette = ['#1A3A5C', '#E8A317', '#27AE60', '#3498DB', '#E67E22', '#8E44AD', '#16A085', '#E74C3C'];

    KomaginAdmin.prototype.refreshDashboardDataFeeds = async function() {
        const safe = action => fetch(`${this.apiBase}?action=${action}`).then(r => r.json()).catch(() => ({ success: false, data: [] }));
        const [
            projectsResult, servicesResult, testimonialsResult, teamResult,
            contactsResult, subscribersResult,
            jobsResult, documentsResult, hireResult, applicationsResult,
            platformsResult
        ] = await Promise.all([
            safe('get_projects'),
            safe('get_services'),
            safe('get_testimonials'),
            safe('get_team'),
            safe('get_contacts'),
            safe('get_subscribers'),
            safe('hr_get_jobs&status=published'),
            safe('documents_get_all'),
            safe('hire_items_get_all'),
            safe('hr_get_applications&status=all'),
            safe('social_get_platforms')
        ]);
        this.dashboardProjects     = projectsResult.success     ? (projectsResult.data     || []) : (this.dashboardProjects     || []);
        this.dashboardServices     = servicesResult.success     ? (servicesResult.data     || []) : (this.dashboardServices     || []);
        this.dashboardTestimonials = testimonialsResult.success ? (testimonialsResult.data || []) : (this.dashboardTestimonials || []);
        this.dashboardTeam         = teamResult.success         ? (teamResult.data         || []) : (this.dashboardTeam         || []);
        this.dashboardContacts     = contactsResult.success     ? (contactsResult.data     || []) : (this.dashboardContacts     || []);
        this.dashboardSubscribers  = subscribersResult.success  ? (subscribersResult.data  || []) : (this.dashboardSubscribers  || []);
        this.dashboardJobs         = jobsResult.success         ? (jobsResult.data         || []) : (this.dashboardJobs         || []);
        this.dashboardDocuments    = documentsResult.success    ? (documentsResult.data    || []) : (this.dashboardDocuments    || []);
        this.dashboardHireItems    = hireResult.success         ? (hireResult.data         || []) : (this.dashboardHireItems    || []);
        this.dashboardApplications = applicationsResult.success ? (applicationsResult.data || []) : (this.dashboardApplications || []);
        this.dashboardPlatforms    = platformsResult.success    ? (platformsResult.data    || []) : (this.dashboardPlatforms    || []);
    };

    KomaginAdmin.prototype.renderDashboardCharts = function() {
        const projects = Array.isArray(this.dashboardProjects) ? this.dashboardProjects : [];
        const services = Array.isArray(this.dashboardServices) ? this.dashboardServices : [];
        const testimonials = Array.isArray(this.dashboardTestimonials) ? this.dashboardTestimonials : [];
        const team = Array.isArray(this.dashboardTeam) ? this.dashboardTeam : [];
        const contacts = Array.isArray(this.dashboardContacts) ? this.dashboardContacts : [];
        const subscribers = Array.isArray(this.dashboardSubscribers) ? this.dashboardSubscribers : [];
        const applications = Array.isArray(this.dashboardApplications) ? this.dashboardApplications : [];
        const jobs = Array.isArray(this.dashboardJobs) ? this.dashboardJobs : [];
        const documents = Array.isArray(this.dashboardDocuments) ? this.dashboardDocuments : [];
        const hireItems = Array.isArray(this.dashboardHireItems) ? this.dashboardHireItems : [];
        const platforms = Array.isArray(this.dashboardPlatforms) ? this.dashboardPlatforms : [];

        const contentData = [
            { label: 'Projects', value: projects.length, color: palette[0] },
            { label: 'Services', value: services.length, color: palette[1] },
            { label: 'Testimonials', value: testimonials.length, color: palette[2] },
            { label: 'Team', value: team.length, color: palette[3] }
        ];

        const projectCategoryCounts = projects.reduce((acc, project) => {
            const key = String(project.category || 'uncategorized').trim() || 'uncategorized';
            acc[key] = (acc[key] || 0) + 1;
            return acc;
        }, {});
        const projectCategoryData = Object.entries(projectCategoryCounts)
            .sort((a, b) => b[1] - a[1])
            .map(([key, value], index) => ({
                label: this.label(key),
                value,
                color: palette[index % palette.length]
            }));

        const volumeData = [
            { label: 'Contact Messages', value: contacts.length, color: palette[0] },
            { label: 'Email Subscriptions', value: subscribers.length, color: palette[1] },
            { label: 'Equipment Listed', value: hireItems.length, color: palette[2] }
        ];

        const appStatusOrder = ['received', 'shortlisted', 'interview', 'hired', 'rejected'];
        const appChartData = appStatusOrder.map((status, index) => ({
            label: this.label(status),
            value: applications.filter(item => String(item.status || 'received') === status).length,
            color: palette[index % palette.length]
        }));

        const hireAvailabilityData = ['available', 'on_request', 'limited', 'booked'].map((status, index) => ({
            label: this.label(status),
            value: hireItems.filter(item => String(item.availability_status || 'available') === status).length,
            color: palette[index % palette.length]
        }));

        const readyChannels = platforms.filter(item => Number(item.posting_ready || 0) === 1).length;
        const verifiedOnly = platforms.filter(item => Number(item.posting_ready || 0) !== 1 && String(item.verification_status || '') === 'verified').length;
        const setupNeeded = Math.max(platforms.length - readyChannels - verifiedOnly, 0);
        const channelReadinessData = [
            { label: 'Ready to Post', value: readyChannels, color: palette[2] },
            { label: 'Verified Only', value: verifiedOnly, color: palette[1] },
            { label: 'Needs Setup', value: setupNeeded, color: palette[7] }
        ];

        const publishingStatusData = [
            { label: 'Published Jobs', value: jobs.filter(item => String(item.status || '') === 'published').length, color: palette[0] },
            { label: 'Live Documents', value: documents.filter(item => Number(item.is_visible || 0) === 1).length, color: palette[1] },
            { label: 'Ready Channels', value: readyChannels, color: palette[2] },
            { label: 'Visible Hire', value: hireItems.filter(item => Number(item.is_active || 0) === 1).length, color: palette[3] }
        ];

        const buckets = this.getRecentMonthBuckets(6);
        const contactSeries = this.bucketRowsByMonth(contacts, 'created_at', buckets);
        const subscriberSeries = this.bucketRowsByMonth(subscribers, 'subscribed_at', buckets);
        const applicationSeries = this.bucketRowsByMonth(applications, 'created_at', buckets);
        const shortlistSeries = this.bucketRowsByMonth(applications.filter(item => String(item.status || '') === 'shortlisted'), 'reviewed_at', buckets);
        const interviewSeries = this.bucketRowsByMonth(applications.filter(item => String(item.status || '') === 'interview'), 'reviewed_at', buckets);

        this.renderDashboardDonut('dashboardContentMixChart', contentData, 'Managed Content');
        this.renderDashboardDonut('dashboardProjectCategoryChart', projectCategoryData, 'Projects');
        this.renderDashboardBars('dashboardVolumeChart', volumeData, 'items');
        this.renderDashboardLine('dashboardTrendChart', buckets.map(item => item.label), [
            { label: 'Contacts', values: contactSeries, color: palette[0] },
            { label: 'Subscribers', values: subscriberSeries, color: palette[1] }
        ]);
        this.renderDashboardBars('dashboardApplicationsChart', appChartData, 'applications');
        this.renderDashboardDonut('dashboardHireAvailabilityChart', hireAvailabilityData, 'Hire Fleet');
        this.renderDashboardDonut('dashboardChannelReadinessChart', channelReadinessData, 'Channels');
        this.renderDashboardLine('dashboardRecruitmentTrendChart', buckets.map(item => item.label), [
            { label: 'Applications', values: applicationSeries, color: palette[3] },
            { label: 'Shortlisted', values: shortlistSeries, color: palette[1] },
            { label: 'Interview', values: interviewSeries, color: palette[2] }
        ]);
        this.renderDashboardBars('dashboardPublishingStatusChart', publishingStatusData, 'live items');
    };

    KomaginAdmin.prototype.getRecentMonthBuckets = function(count = 6) {
        const now = new Date();
        const buckets = [];
        for (let offset = count - 1; offset >= 0; offset--) {
            const date = new Date(now.getFullYear(), now.getMonth() - offset, 1);
            const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            buckets.push({
                key,
                label: date.toLocaleString('en-US', { month: 'short' })
            });
        }
        return buckets;
    };

    KomaginAdmin.prototype.bucketRowsByMonth = function(rows, dateKey, buckets) {
        const counts = Object.fromEntries((buckets || []).map(item => [item.key, 0]));
        (rows || []).forEach(row => {
            const raw = row?.[dateKey];
            if (!raw) return;
            const date = new Date(raw);
            if (Number.isNaN(date.getTime())) return;
            const key = `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}`;
            if (Object.prototype.hasOwnProperty.call(counts, key)) counts[key] += 1;
        });
        return buckets.map(item => counts[item.key] || 0);
    };

    KomaginAdmin.prototype.renderDashboardDonut = function(targetId, items, centerLabel = '') {
        const target = document.getElementById(targetId);
        if (!target) return;
        const total = items.reduce((sum, item) => sum + Number(item.value || 0), 0);
        if (!total) {
            target.innerHTML = `<div class="chart-empty">No dashboard data available yet for this chart.</div>`;
            return;
        }
        const radius = 68;
        const circumference = 2 * Math.PI * radius;
        let offset = 0;
        const defs = items.map((item, i) => `<linearGradient id="donutGrad${targetId}${i}" x1="0%" y1="0%" x2="100%" y2="100%"><stop offset="0%" stop-color="${item.color}" stop-opacity="1"/><stop offset="100%" stop-color="${item.color}" stop-opacity="0.72"/></linearGradient>`).join('');
        const segments = items.map((item, i) => {
            const value = Number(item.value || 0);
            const length = (value / total) * circumference;
            const segment = `<circle cx="110" cy="110" r="${radius}" fill="none" stroke="url(#donutGrad${targetId}${i})" stroke-width="26" stroke-dasharray="${length} ${circumference - length}" stroke-dashoffset="${-offset}" transform="rotate(-90 110 110)" stroke-linecap="round" style="filter:drop-shadow(0 2px 4px rgba(0,0,0,0.08));"></circle>`;
            offset += length;
            return segment;
        }).join('');
        const legend = items.map(item => {
            const pct = Math.round((Number(item.value || 0) / total) * 100);
            return `<div class="chart-legend-item"><span class="chart-legend-label"><span class="chart-swatch" style="background:${item.color}"></span>${this.escapeHtml(item.label)}</span><strong>${this.escapeHtml(String(item.value || 0))} <span class="chart-legend-pct">${pct}%</span></strong></div>`;
        }).join('');
        target.innerHTML = `
            <div class="chart-scroll">
                <div class="chart-donut-layout chart-scroll-inner-medium">
                    <svg class="chart-svg donut-svg" viewBox="0 0 220 220" aria-hidden="true">
                        <defs>${defs}</defs>
                        <circle cx="110" cy="110" r="${radius}" fill="none" stroke="#E9ECEF" stroke-width="26"></circle>
                        ${segments}
                        <text x="110" y="100" text-anchor="middle" font-size="12" fill="#6C757D" font-weight="500" letter-spacing="0.5">${this.escapeHtml(centerLabel)}</text>
                        <text x="110" y="128" text-anchor="middle" font-size="32" font-weight="800" fill="#1A3A5C">${this.escapeHtml(String(total))}</text>
                    </svg>
                    <div class="chart-legend">${legend}</div>
                </div>
            </div>
        `;
    };

    KomaginAdmin.prototype.renderDashboardBars = function(targetId, items, unitLabel = 'items') {
        const target = document.getElementById(targetId);
        if (!target) return;
        const rows = items.filter(item => Number(item.value || 0) >= 0);
        const max = Math.max(...rows.map(item => Number(item.value || 0)), 0);
        if (!max) {
            target.innerHTML = `<div class="chart-empty">No dashboard data available yet for this chart.</div>`;
            return;
        }
        const defs = rows.map((item, i) => `<linearGradient id="barGrad${targetId}${i}" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="${item.color}"/><stop offset="100%" stop-color="${item.color}" stop-opacity="0.55"/></linearGradient>`).join('');
        const gridLines = [0, 0.25, 0.5, 0.75, 1].map(t => `<line x1="0" y1="${160 - (t * 160)}" x2="100%" y2="${160 - (t * 160)}" stroke="rgba(0,0,0,0.04)" stroke-width="1" stroke-dasharray="4 4"/>`).join('');
        target.innerHTML = `
            <div class="chart-scroll">
                <div class="chart-scroll-inner-wide">
                <svg class="bar-grid" viewBox="0 0 400 160" preserveAspectRatio="none" aria-hidden="true">${defs}${gridLines}</svg>
                <div class="chart-bars">
                    ${rows.map((item, i) => {
                        const value = Number(item.value || 0);
                        const height = Math.max(10, Math.round((value / max) * 160));
                        return `<div class="chart-bar-group">
                            <div class="chart-bar-value">${this.escapeHtml(String(value))}</div>
                            <div class="chart-bar-stack"><div class="chart-bar" style="height:${height}px;background:linear-gradient(180deg, ${item.color} 0%, ${item.color}dd 100%);"></div></div>
                            <div class="chart-bar-label">${this.escapeHtml(item.label)}</div>
                        </div>`;
                    }).join('')}
                </div>
                <div class="line-chart-meta">
                    <span class="line-chart-pill"><span class="chart-swatch" style="background:${rows[0]?.color || palette[0]}"></span>Scaled by highest ${this.escapeHtml(unitLabel)}</span>
                    <span class="line-chart-pill">Peak: ${this.escapeHtml(String(max))}</span>
                </div>
                </div>
            </div>
        `;
    };

    KomaginAdmin.prototype.renderDashboardLine = function(targetId, labels, seriesList) {
        const target = document.getElementById(targetId);
        if (!target) return;
        const flattened = seriesList.flatMap(series => series.values || []);
        const max = Math.max(...flattened, 0);
        if (!max) {
            target.innerHTML = `<div class="chart-empty">No trend data is available yet for this chart.</div>`;
            return;
        }
        const width = 460;
        const height = 220;
        const padding = 28;
        const stepX = labels.length > 1 ? (width - padding * 2) / (labels.length - 1) : 0;
        const toPoint = (index, value) => {
            const x = padding + (index * stepX);
            const y = height - padding - ((value / max) * (height - padding * 2));
            return [x, y];
        };
        const smoothPath = (points) => {
            if (points.length < 2) return '';
            if (points.length === 2) return `M ${points[0][0]} ${points[0][1]} L ${points[1][0]} ${points[1][1]}`;
            const d = [`M ${points[0][0]} ${points[0][1]}`];
            for (let i = 0; i < points.length - 1; i++) {
                const p0 = points[Math.max(0, i - 1)];
                const p1 = points[i];
                const p2 = points[i + 1];
                const p3 = points[Math.min(points.length - 1, i + 2)];
                const cp1x = p1[0] + (p2[0] - p0[0]) * 0.18;
                const cp1y = p1[1] + (p2[1] - p0[1]) * 0.18;
                const cp2x = p2[0] - (p3[0] - p1[0]) * 0.18;
                const cp2y = p2[1] - (p3[1] - p1[1]) * 0.18;
                d.push(`C ${cp1x} ${cp1y}, ${cp2x} ${cp2y}, ${p2[0]} ${p2[1]}`);
            }
            return d.join(' ');
        };
        const areaPath = (points) => {
            if (points.length < 2) return '';
            const path = smoothPath(points);
            const last = points[points.length - 1];
            const first = points[0];
            return `${path} L ${last[0]} ${height - padding} L ${first[0]} ${height - padding} Z`;
        };
        const defs = seriesList.map((series, i) => `<linearGradient id="areaGrad${targetId}${i}" x1="0" y1="0" x2="0" y2="1"><stop offset="0%" stop-color="${series.color}" stop-opacity="0.22"/><stop offset="100%" stop-color="${series.color}" stop-opacity="0.02"/></linearGradient>`).join('');
        const areas = seriesList.map((series, i) => {
            const points = (series.values || []).map((value, index) => toPoint(index, Number(value || 0)));
            return `<path d="${areaPath(points)}" fill="url(#areaGrad${targetId}${i})" stroke="none"></path>`;
        }).join('');
        const lines = seriesList.map(series => {
            const points = (series.values || []).map((value, index) => toPoint(index, Number(value || 0)));
            const path = smoothPath(points);
            return `
                <path fill="none" stroke="${series.color}" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" d="${path}" style="filter:drop-shadow(0 2px 3px rgba(0,0,0,0.08));"></path>
                ${points.map((point, idx) => `<circle cx="${point[0]}" cy="${point[1]}" r="3.5" fill="${series.color}" stroke="#fff" stroke-width="1.5" data-value="${series.values[idx]}"></circle>`).join('')}
            `;
        }).join('');
        const gridLines = [0, 0.25, 0.5, 0.75, 1].map(t => {
            const y = height - padding - (t * (height - padding * 2));
            return `<line x1="${padding}" y1="${y}" x2="${width - padding}" y2="${y}" stroke="rgba(0,0,0,0.04)" stroke-width="1" stroke-dasharray="4 4"/>`;
        }).join('');
        const xLabels = labels.map((label, index) => {
            const x = padding + (index * stepX);
            return `<text x="${x}" y="${height - 6}" text-anchor="middle" font-size="11" fill="#6C757D">${this.escapeHtml(label)}</text>`;
        }).join('');
        const legend = seriesList.map(series => `<span class="line-chart-pill"><span class="chart-swatch" style="background:${series.color}"></span>${this.escapeHtml(series.label)}</span>`).join('');
        target.innerHTML = `
            <div class="chart-scroll">
                <div class="chart-scroll-inner-wide">
                <svg class="chart-svg" viewBox="0 0 ${width} ${height}" aria-hidden="true">
                    <defs>${defs}</defs>
                    <line x1="${padding}" y1="${height - padding}" x2="${width - padding}" y2="${height - padding}" stroke="#D9E1E7" stroke-width="1.5"></line>
                    <line x1="${padding}" y1="${padding}" x2="${padding}" y2="${height - padding}" stroke="#D9E1E7" stroke-width="1.5"></line>
                    ${gridLines}
                    ${areas}
                    ${lines}
                    ${xLabels}
                </svg>
                <div class="line-chart-meta">${legend}</div>
                </div>
            </div>
        `;
    };

    const wrappedLoaders = ['loadProjects', 'loadServices', 'loadTestimonials', 'loadTeam', 'loadContacts', 'loadSubscribers', 'loadDocuments', 'loadJobs'];
    wrappedLoaders.forEach(name => {
        const original = KomaginAdmin.prototype[name];
        if (typeof original !== 'function') return;
        KomaginAdmin.prototype[name] = async function(...args) {
            const result = await original.apply(this, args);
            this.renderDashboardCharts();
            return result;
        };
    });

    const oldLoadDashboard = KomaginAdmin.prototype.loadDashboardData;
    KomaginAdmin.prototype.loadDashboardData = async function(...args) {
        const result = await oldLoadDashboard.apply(this, args);
        await this.refreshDashboardDataFeeds();
        const dashboardCharts = document.querySelector('.dashboard-charts');
        if (dashboardCharts) dashboardCharts.style.display = 'grid';
        this.renderDashboardCharts();
        return result;
    };

    const oldLoadRecentApplications = KomaginAdmin.prototype.loadRecentApplications;
    KomaginAdmin.prototype.loadRecentApplications = async function(...args) {
        const result = await oldLoadRecentApplications.apply(this, args);
        await this.refreshDashboardDataFeeds();
        this.renderDashboardCharts();
        return result;
    };
})();

// Initialize admin panel
let komaginAdmin;
document.addEventListener('DOMContentLoaded', () => {
    komaginAdmin = new KomaginAdmin();
    window.komaginAdmin = komaginAdmin;
});

// KOMAGIN CLEAN UI PATCH: empty-state polish and newsletter attachments.
(function(){
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__komaginCleanUiPatch) return;
    KomaginAdmin.prototype.__komaginCleanUiPatch = true;

    const oldRenderUpgradeTable = KomaginAdmin.prototype.renderUpgradeTable;
    KomaginAdmin.prototype.renderUpgradeTable = function(section, rows) {
        if (rows && rows.length) return oldRenderUpgradeTable.call(this, section, rows);
        const starters = {
            'social-setup': ['fa-plug','No social platforms configured','Open Configure on a platform card to paste API credentials and test the connection.'],
            'social-posts': ['fa-share-alt','No social posts yet','Compose or schedule an announcement after at least one platform is configured.'],
            'branch-milestones': ['fa-flag-checkered','No milestones yet','Milestones appear after branch projects are created and weighted milestones are added.'],
            'branch-rfis': ['fa-question-circle','No RFIs yet','Branch manager RFIs will appear here for review, official answers, and response letters.'],
            'branch-kpis': ['fa-chart-pie','No KPI records yet','Choose a period and click Generate KPIs to build the monthly branch dashboard.'],
            'branch-site-reports': ['fa-clipboard-list','No site reports yet','Reports submitted from the branch portal will appear here for verification.']
        };
        const s = starters[section] || ['fa-inbox','No records found','Use the action button above to create the first record.'];
        return `<div class="articles-list"><div class="empty-state module-empty"><i class="fas ${s[0]}"></i><h3>${s[1]}</h3><p>${s[2]}</p></div></div>`;
    };

    const oldInitNewsletter = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInitNewsletter.call(this);

        const tmpl = document.getElementById('newsletterTemplateBtn');
        if (tmpl && !tmpl.dataset.bound) {
            tmpl.dataset.bound = '1';
            const today = new Date().toLocaleDateString('en-AU', { day:'numeric', month:'long', year:'numeric' });
            tmpl.addEventListener('click', () => {
                document.getElementById('newsletterSubject').value = 'Komagin Limited ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â Project & Operations Update | ' + today;
                document.getElementById('newsletterContent').value =
`Dear Valued Partners and Subscribers,

We are pleased to share the latest update from Komagin Limited ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â Papua New Guinea's leading civil and structural engineering company.

ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â
ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â°ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¸ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬ÃƒÂ¢Ã¢â‚¬Å¾Ã‚Â¢ PROJECT HIGHLIGHTS
ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â

Project Name: [Enter project name]
Branch / Location: [Enter location]
Progress: [e.g. 65% complete]
Key Achievement: [Describe milestone reached]
Expected Completion: [Enter date]

ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â
ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â°ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¸ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¹ OPERATIONS UPDATE
ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â

Safety Record: [e.g. 30 days incident-free]
Equipment Deployed: [List key assets]
Labour on Site: [Number of workers]
Upcoming Work: [Next phase or milestone]

ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â
ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â°ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¸ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â£ COMPANY NEWS
ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â

[Add any company news, announcements, or events here]

ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â
ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â°ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¸ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Â¦ÃƒÂ¢Ã¢â€šÂ¬Ã…â€œÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¦ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â½ ATTACHED DOCUMENTS
ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â

1. [Document name ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â e.g. Monthly Site Report ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â March 2025]
2. [Document name ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â e.g. Financial Summary Q1 2025]
3. [Document name ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â e.g. Safety Compliance Certificate]

ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â

Thank you for your continued support and partnership with Komagin Limited.

For enquiries, contact us at: info@komagin.com | +675 XXX XXXX

Warm regards,

The Komagin Limited Team
Komagin Limited | Port Moresby, Papua New Guinea
www.komagin.com.pg

ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡Ãƒâ€šÃ‚Â¬ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â
[To unsubscribe from this newsletter, reply with UNSUBSCRIBE]`;
            });
        }

        // Attachment file list display
        const attachInput = document.getElementById('newsletterAttachments');
        if (attachInput && !attachInput.dataset.bound) {
            attachInput.dataset.bound = '1';
            attachInput.addEventListener('change', () => {
                let listEl = document.getElementById('attachmentFileList');
                if (!listEl) {
                    listEl = document.createElement('div');
                    listEl.id = 'attachmentFileList';
                    listEl.style.cssText = 'margin-top:8px;display:flex;flex-wrap:wrap;gap:8px';
                    attachInput.parentNode.appendChild(listEl);
                }
                listEl.innerHTML = '';
                Array.from(attachInput.files).forEach(file => {
                    const tag = document.createElement('span');
                    tag.style.cssText = 'background:#e9ecef;border-radius:4px;padding:4px 10px;font-size:.78rem;display:inline-flex;align-items:center;gap:5px';
                    const sizeKb = (file.size / 1024).toFixed(0);
                    tag.innerHTML = `<i class="fas fa-paperclip"></i> ${this.escapeHtml(file.name)} <small style="color:#6c757d">(${sizeKb} KB)</small>`;
                    listEl.appendChild(tag);
                });
            });
        }

        // Newsletter preview
        const previewBtn = document.getElementById('previewNewsletterBtn');
        if (previewBtn && !previewBtn.dataset.bound) {
            previewBtn.dataset.bound = '1';
            previewBtn.addEventListener('click', () => {
                const subject = document.getElementById('newsletterSubject')?.value || '(No subject)';
                const content = document.getElementById('newsletterContent')?.value || '';
                const attachments = Array.from(document.getElementById('newsletterAttachments')?.files || []);
                const preview = `<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif">
                  <div style="background:#1A3A5C;padding:24px 28px;text-align:center">
                    <h2 style="color:#E8A317;margin:0">KOMAGIN LIMITED</h2>
                    <p style="color:rgba(255,255,255,.8);margin:4px 0 0;font-size:.85rem">Papua New Guinea</p>
                  </div>
                  <div style="background:#fff;padding:28px;border:1px solid #e9ecef">
                    <h3 style="color:#1A3A5C;border-bottom:2px solid #E8A317;padding-bottom:8px">${this.escapeHtml(subject)}</h3>
                    <div style="white-space:pre-wrap;font-size:.9rem;line-height:1.8;color:#333">${this.escapeHtml(content)}</div>
                    ${attachments.length ? `<div style="margin-top:20px;padding:14px;background:#f8f9fa;border-radius:6px"><strong style="color:#1A3A5C">Attachments (${attachments.length}):</strong><ul style="margin:8px 0 0;padding-left:20px">${attachments.map(f => `<li style="font-size:.85rem">${this.escapeHtml(f.name)} (${(f.size/1024).toFixed(0)} KB)</li>`).join('')}</ul></div>` : ''}
                  </div>
                  <div style="background:#f8f9fa;padding:12px 28px;text-align:center;font-size:.75rem;color:#999;border-top:1px solid #e9ecef">
                    ÃƒÆ’Ã†â€™Ãƒâ€ Ã¢â‚¬â„¢ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€¦Ã‚Â¡ÃƒÆ’Ã†â€™ÃƒÂ¢Ã¢â€šÂ¬Ã…Â¡ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â© Komagin Limited | Port Moresby, Papua New Guinea
                  </div>
                </div>`;
                document.getElementById('upgradeModalTitle').textContent = 'Newsletter Preview';
                document.getElementById('upgradeModalBody').innerHTML = `<div style="background:#e9ecef;padding:20px;border-radius:8px">${preview}</div>`;
                this.showModal('upgradeModal');
            });
        }
    };

    const oldSendNewsletter = KomaginAdmin.prototype.sendNewsletter;
    KomaginAdmin.prototype.sendNewsletter = async function(isTest = false) {
        const subject = document.getElementById('newsletterSubject')?.value.trim() || '';
        const content = document.getElementById('newsletterContent')?.value.trim() || '';
        const recipientType = document.querySelector('input[name="recipientType"]:checked')?.value || 'all';
        const attachmentInput = document.getElementById('newsletterAttachments');
        const hasAttachments = attachmentInput && attachmentInput.files && attachmentInput.files.length > 0;
        if (!hasAttachments && (!subject || !content)) return oldSendNewsletter.call(this, isTest);
        if (!subject || !content) {
            this.showError('Newsletter subject and content are required.');
            return;
        }
        this.showLoading(true);
        try {
            const formData = new FormData();
            formData.append('subject', subject);
            formData.append('content', content);
            formData.append('recipient_type', isTest ? 'test' : recipientType);
            Array.from(attachmentInput?.files || []).forEach(file => formData.append('attachments[]', file));
            const response = await fetch(`${this.apiBase}?action=send_newsletter`, { method:'POST', body: formData });
            const result = await response.json();
            if (!result.success) throw new Error(result.error || 'Send failed');
            this.showSuccess(result.message || 'Newsletter sent.');
            if (!isTest) {
                document.getElementById('newsletterSubject').value = '';
                document.getElementById('newsletterContent').value = '';
                if (attachmentInput) attachmentInput.value = '';
            }
        } catch (error) {
            this.showError('Failed to send newsletter: ' + error.message);
        } finally {
            this.showLoading(false);
        }
    };
})();

// Runs after every admin extension so the newsletter template button keeps the clean template.
(function(){
    if (typeof KomaginAdmin === 'undefined' || KomaginAdmin.prototype.__newsletterTemplateAfterCleanFix) return;
    KomaginAdmin.prototype.__newsletterTemplateAfterCleanFix = true;
    const oldInit = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInit.call(this);
        const oldBtn = document.getElementById('newsletterTemplateBtn');
        if (!oldBtn || oldBtn.dataset.afterCleanTemplateBound === '1') return;
        const btn = oldBtn.cloneNode(true);
        oldBtn.parentNode.replaceChild(btn, oldBtn);
        btn.dataset.afterCleanTemplateBound = '1';
        btn.addEventListener('click', () => {
            const today = new Date().toLocaleDateString('en-AU', { day:'numeric', month:'long', year:'numeric' });
            const subject = document.getElementById('newsletterSubject');
            const content = document.getElementById('newsletterContent');
            if (subject) subject.value = 'Komagin Limited - Project & Operations Update | ' + today;
            if (content) content.value = this.buildNewsletterTemplate ? this.buildNewsletterTemplate(today) : '';
            this.showSuccess('Decorated newsletter template loaded');
        });
    };
})();

// Final newsletter preview rebinding keeps the preview footer clean and avoids stale broken-text handlers.
(function(){
    if (typeof KomaginAdmin === 'undefined' || KomaginAdmin.prototype.__newsletterPreviewFooterFix) return;
    KomaginAdmin.prototype.__newsletterPreviewFooterFix = true;
    const oldInit = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInit.call(this);
        const oldBtn = document.getElementById('previewNewsletterBtn');
        if (!oldBtn || oldBtn.dataset.previewFooterFixBound === '1') return;
        const btn = oldBtn.cloneNode(true);
        oldBtn.parentNode.replaceChild(btn, oldBtn);
        btn.dataset.previewFooterFixBound = '1';
        btn.addEventListener('click', () => {
            const subject = document.getElementById('newsletterSubject')?.value || '(No subject)';
            const content = document.getElementById('newsletterContent')?.value || '';
            const attachments = Array.from(document.getElementById('newsletterAttachments')?.files || []);
            const preview = `<div style="max-width:600px;margin:0 auto;font-family:Arial,sans-serif">
              <div style="background:#1A3A5C;padding:24px 28px;text-align:center">
                <h2 style="color:#E8A317;margin:0">KOMAGIN LIMITED</h2>
                <p style="color:rgba(255,255,255,.8);margin:4px 0 0;font-size:.85rem">Papua New Guinea</p>
              </div>
              <div style="background:#fff;padding:28px;border:1px solid #e9ecef">
                <h3 style="color:#1A3A5C;border-bottom:2px solid #E8A317;padding-bottom:8px">${this.escapeHtml(subject)}</h3>
                <div style="white-space:pre-wrap;font-size:.9rem;line-height:1.8;color:#333">${this.escapeHtml(content)}</div>
                ${attachments.length ? `<div style="margin-top:20px;padding:14px;background:#f8f9fa;border-radius:6px"><strong style="color:#1A3A5C">Attachments (${attachments.length}):</strong><ul style="margin:8px 0 0;padding-left:20px">${attachments.map(file => `<li style="font-size:.85rem">${this.escapeHtml(file.name)} (${(file.size / 1024).toFixed(0)} KB)</li>`).join('')}</ul></div>` : ''}
              </div>
              <div style="background:#f8f9fa;padding:12px 28px;text-align:center;font-size:.75rem;color:#999;border-top:1px solid #e9ecef">
                Copyright © Komagin Limited | Port Moresby, Papua New Guinea
              </div>
            </div>`;
            document.getElementById('upgradeModalTitle').textContent = 'Newsletter Preview';
            document.getElementById('upgradeModalBody').innerHTML = `<div style="background:#e9ecef;padding:20px;border-radius:8px">${preview}</div>`;
            this.showModal('upgradeModal');
        });
    };
})();

// Media library chooser and full icon library, layered into the existing admin UI without redesigning it.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__mediaLibraryAndIconChooserPatch) return;
    KomaginAdmin.prototype.__mediaLibraryAndIconChooserPatch = true;

    const ICON_LIBRARY = [
        'fa-cog','fa-cogs','fa-gears','fa-screwdriver-wrench','fa-toolbox','fa-tools',
        'fa-drafting-compass','fa-ruler-combined','fa-compass','fa-hard-hat','fa-helmet-safety','fa-people-carry-box',
        'fa-building','fa-building-columns','fa-city','fa-road','fa-bridge','fa-warehouse','fa-industry','fa-mountain','fa-water','fa-bolt',
        'fa-truck','fa-truck-monster','fa-dumpster','fa-bus','fa-ship','fa-plane',
        'fa-chart-line','fa-chart-pie','fa-chart-bar','fa-diagram-project','fa-tasks','fa-list-check','fa-clipboard-check','fa-clipboard-list',
        'fa-users','fa-user-tie','fa-user-gear','fa-user-check','fa-user-group','fa-handshake','fa-hands-helping','fa-people-group',
        'fa-earth-asia','fa-globe','fa-location-dot','fa-map-location-dot','fa-map-marked-alt','fa-building-user',
        'fa-phone','fa-phone-volume','fa-envelope','fa-paper-plane','fa-comment','fa-comments','fa-bullhorn','fa-share-alt',
        'fa-star','fa-award','fa-medal','fa-shield-alt','fa-shield-halved','fa-lock','fa-key',
        'fa-file-alt','fa-file-lines','fa-file-contract','fa-file-signature','fa-file-invoice','fa-file-circle-check','fa-file-shield','fa-folder-open',
        'fa-image','fa-images','fa-camera','fa-video','fa-photo-film',
        'fa-newspaper','fa-briefcase','fa-graduation-cap','fa-church','fa-football-ball','fa-seedling',
        'fa-recycle','fa-leaf','fa-tree','fa-solar-panel','fa-lightbulb',
        'fa-calendar','fa-calendar-check','fa-clock','fa-history','fa-bell','fa-bolt-lightning',
        'fa-search','fa-eye','fa-pen-to-square','fa-plus','fa-minus','fa-check','fa-xmark'
    ];

    function prettyIconLabel(icon) {
        return String(icon || '')
            .replace(/^fa-/, '')
            .replace(/-/g, ' ')
            .replace(/\b\w/g, (char) => char.toUpperCase());
    }

    KomaginAdmin.prototype.ensureChooserUi = function() {
        if (document.getElementById('komaginAdminChooserStyles')) return;
        const style = document.createElement('style');
        style.id = 'komaginAdminChooserStyles';
        style.textContent = `
            .chooser-toolbar{display:flex;gap:12px;flex-wrap:wrap;margin-bottom:16px}
            .chooser-toolbar input,.chooser-toolbar select{flex:1 1 220px;padding:12px 14px;border:1px solid #d7dee6;border-radius:10px;background:#fff}
            .chooser-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(180px,1fr));gap:14px;max-height:56vh;overflow:auto;padding-right:4px}
            .chooser-card{border:1px solid #d7dee6;border-radius:12px;background:#fff;overflow:hidden;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
            .chooser-card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(15,23,42,.08)}
            .chooser-card.is-active{border-color:#1a3a5c;box-shadow:0 0 0 2px rgba(26,58,92,.12)}
            .chooser-card-thumb{height:118px;background:#eef2f6;display:flex;align-items:center;justify-content:center;color:#7a8794}
            .chooser-card-thumb img{width:100%;height:100%;object-fit:cover}
            .chooser-card-body{padding:12px}
            .chooser-card-title{font-weight:700;color:#1a3a5c;margin-bottom:6px;word-break:break-word}
            .chooser-card-meta{font-size:.82rem;color:#65717c;display:flex;justify-content:space-between;gap:8px;flex-wrap:wrap}
            .chooser-empty{padding:26px 16px;text-align:center;color:#65717c;background:#f8fafc;border:1px dashed #d7dee6;border-radius:12px}
            .chooser-summary{font-size:.9rem;color:#4c5965;margin:-4px 0 12px}
            .image-upload-actions,.icon-input-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:10px}
            .media-library-inline-note{display:block;margin-top:8px;color:#65717c;font-size:.82rem}
            .icon-library-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(120px,1fr));gap:12px;max-height:56vh;overflow:auto;padding-right:4px}
            .icon-library-card{border:1px solid #d7dee6;border-radius:12px;background:#fff;padding:14px 10px;text-align:center;cursor:pointer;transition:transform .18s ease,box-shadow .18s ease,border-color .18s ease}
            .icon-library-card:hover{transform:translateY(-2px);box-shadow:0 10px 24px rgba(15,23,42,.08)}
            .icon-library-card.is-active{border-color:#1a3a5c;box-shadow:0 0 0 2px rgba(26,58,92,.12)}
            .icon-library-card i{font-size:1.45rem;color:#1a3a5c;margin-bottom:10px}
            .icon-library-card strong{display:block;font-size:.88rem;color:#243442;margin-bottom:4px}
            .icon-library-card span{display:block;font-size:.75rem;color:#65717c;word-break:break-word}
        `;
        document.head.appendChild(style);

        document.body.insertAdjacentHTML('beforeend', `
            <div id="mediaLibraryModal" class="modal">
                <div class="modal-content large-modal">
                    <div class="modal-header">
                        <h3 id="mediaLibraryModalTitle">Choose from Media Library</h3>
                        <button class="modal-close" type="button" id="mediaLibraryCloseBtn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="chooser-toolbar">
                            <input type="text" id="mediaLibrarySearchInput" placeholder="Search images by filename, title, or folder">
                            <select id="mediaLibraryCategoryFilter"><option value="">All folders</option></select>
                        </div>
                        <div class="chooser-summary" id="mediaLibrarySelectionSummary">Loading media library...</div>
                        <div class="chooser-grid" id="mediaLibraryGrid"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" id="mediaLibraryCancelBtn">Cancel</button>
                        <button class="btn btn-primary" type="button" id="mediaLibraryConfirmBtn">Use Selected Image</button>
                    </div>
                </div>
            </div>
            <div id="iconLibraryModal" class="modal">
                <div class="modal-content large-modal">
                    <div class="modal-header">
                        <h3 id="iconLibraryModalTitle">Choose an Icon</h3>
                        <button class="modal-close" type="button" id="iconLibraryCloseBtn">&times;</button>
                    </div>
                    <div class="modal-body">
                        <div class="chooser-toolbar">
                            <input type="text" id="iconLibrarySearchInput" placeholder="Search icons by keyword or Font Awesome class">
                        </div>
                        <div class="icon-library-grid" id="iconLibraryGrid"></div>
                    </div>
                    <div class="modal-footer">
                        <button class="btn btn-secondary" type="button" id="iconLibraryCancelBtn">Cancel</button>
                    </div>
                </div>
            </div>
        `);

        const closeMedia = () => this.hideModal('mediaLibraryModal');
        const closeIcon = () => this.hideModal('iconLibraryModal');
        document.getElementById('mediaLibraryCloseBtn')?.addEventListener('click', closeMedia);
        document.getElementById('mediaLibraryCancelBtn')?.addEventListener('click', closeMedia);
        document.getElementById('mediaLibraryConfirmBtn')?.addEventListener('click', () => this.commitMediaLibrarySelection());
        document.getElementById('mediaLibrarySearchInput')?.addEventListener('input', () => this.renderMediaLibraryPicker());
        document.getElementById('mediaLibraryCategoryFilter')?.addEventListener('change', () => this.renderMediaLibraryPicker());
        document.getElementById('mediaLibraryModal')?.addEventListener('click', (event) => {
            if (event.target.id === 'mediaLibraryModal') closeMedia();
        });

        document.getElementById('iconLibraryCloseBtn')?.addEventListener('click', closeIcon);
        document.getElementById('iconLibraryCancelBtn')?.addEventListener('click', closeIcon);
        document.getElementById('iconLibrarySearchInput')?.addEventListener('input', () => this.renderIconLibraryPicker());
        document.getElementById('iconLibraryModal')?.addEventListener('click', (event) => {
            if (event.target.id === 'iconLibraryModal') closeIcon();
        });
    };

    KomaginAdmin.prototype.loadMediaLibraryAssets = async function(force = false) {
        if (!force && Array.isArray(this._mediaLibraryAssetsCache)) return this._mediaLibraryAssetsCache;
        const result = await fetch(`${this.apiBase}?action=media_library_get_assets`).then((response) => response.json());
        if (!result.success) throw new Error(result.error || 'Media library images could not be loaded');
        this._mediaLibraryAssetsCache = Array.isArray(result.data) ? result.data : [];
        this._mediaLibraryCategoriesCache = Array.isArray(result.categories) ? result.categories : [];
        return this._mediaLibraryAssetsCache;
    };

    KomaginAdmin.prototype.findLinkedHiddenInput = function(fileInput) {
        const formGroup = fileInput.closest('.form-group') || fileInput.parentElement;
        if (!formGroup) return null;
        if (fileInput.dataset.target) {
            return formGroup.querySelector(`input[type="hidden"][name="${fileInput.dataset.target}"]`);
        }
        if (fileInput.name === 'featured_image') {
            return formGroup.querySelector('input[type="hidden"][name="featured_image_path"]');
        }
        if (fileInput.id) {
            const byId = document.getElementById(`${fileInput.id}Path`);
            if (byId) return byId;
        }
        return formGroup.querySelector('input[type="hidden"]');
    };

    KomaginAdmin.prototype.ensureImagePreviewBlock = function(fileInput) {
        const formGroup = fileInput.closest('.form-group') || fileInput.parentElement;
        if (!formGroup) return null;
        let preview = formGroup.querySelector('.image-preview');
        if (preview) return preview;
        preview = document.createElement('div');
        preview.className = 'image-preview';
        preview.innerHTML = '<i class="fas fa-image"></i><span>No image selected</span>';
        fileInput.insertAdjacentElement('beforebegin', preview);
        return preview;
    };

    KomaginAdmin.prototype.setPreviewFromAsset = function(preview, asset, altText = 'Selected image') {
        if (!preview || !asset) return;
        const url = this.escapeHtml(asset.file_url || this.resolveAdminAssetUrl(asset.file_path || ''));
        preview.innerHTML = `<img src="${url}" alt="${this.escapeHtml(altText)}">`;
        preview.classList.add('has-image');
    };

    KomaginAdmin.prototype.applyMediaLibrarySelection = function(hiddenInput, preview, asset, noteEl = null) {
        if (hiddenInput) hiddenInput.value = asset.file_path || '';
        this.setPreviewFromAsset(preview, asset, asset.display_name || asset.original_name || 'Selected image');
        if (noteEl) {
            noteEl.textContent = `${asset.display_name || asset.original_name || asset.filename || 'Image'} selected from the media library.`;
        }
    };

    KomaginAdmin.prototype.openMediaLibraryPicker = async function(options = {}) {
        this.ensureChooserUi();
        this.showLoading(true);
        try {
            const assets = await this.loadMediaLibraryAssets(options.forceReload === true);
            this._mediaLibraryPickerState = {
                title: options.title || 'Choose from Media Library',
                multiple: options.multiple === true,
                onConfirm: typeof options.onConfirm === 'function' ? options.onConfirm : null,
                selectedIds: new Set(options.selectedIds || [])
            };
            document.getElementById('mediaLibraryModalTitle').textContent = this._mediaLibraryPickerState.title;
            const categoryFilter = document.getElementById('mediaLibraryCategoryFilter');
            if (categoryFilter) {
                const current = categoryFilter.value;
                categoryFilter.innerHTML = `<option value="">All folders</option>${(this._mediaLibraryCategoriesCache || []).map((category) => `<option value="${this.escapeHtml(category.id)}">${this.escapeHtml(category.name || category.slug || 'Media')}</option>`).join('')}`;
                categoryFilter.value = current;
            }
            const searchInput = document.getElementById('mediaLibrarySearchInput');
            if (searchInput && options.resetSearch !== false) searchInput.value = '';
            this.renderMediaLibraryPicker(assets);
            this.showModal('mediaLibraryModal');
        } catch (error) {
            this.showError(error.message || 'Media library could not be opened');
        } finally {
            this.showLoading(false);
        }
    };

    KomaginAdmin.prototype.renderMediaLibraryPicker = function(cachedAssets = null) {
        const grid = document.getElementById('mediaLibraryGrid');
        const summary = document.getElementById('mediaLibrarySelectionSummary');
        const confirmBtn = document.getElementById('mediaLibraryConfirmBtn');
        if (!grid || !summary) return;
        const assets = Array.isArray(cachedAssets) ? cachedAssets : (this._mediaLibraryAssetsCache || []);
        const state = this._mediaLibraryPickerState || { multiple: false, selectedIds: new Set() };
        const searchTerm = String(document.getElementById('mediaLibrarySearchInput')?.value || '').trim().toLowerCase();
        const categoryId = String(document.getElementById('mediaLibraryCategoryFilter')?.value || '').trim();
        const filtered = assets.filter((asset) => {
            if (categoryId && String(asset.category_id || '') !== categoryId) return false;
            if (!searchTerm) return true;
            const haystack = [
                asset.display_name,
                asset.original_name,
                asset.title,
                asset.category_name,
                asset.file_path
            ].join(' ').toLowerCase();
            return haystack.includes(searchTerm);
        });
        summary.textContent = state.multiple
            ? `${filtered.length} image(s) available. Select one or more files from the library.`
            : `${filtered.length} image(s) available. Select one file from the library.`;
        if (confirmBtn) confirmBtn.textContent = state.multiple ? 'Use Selected Images' : 'Use Selected Image';
        if (!filtered.length) {
            grid.innerHTML = `<div class="chooser-empty" style="grid-column:1/-1;"><i class="fas fa-images"></i><p>No image assets matched your search.</p></div>`;
            if (confirmBtn) confirmBtn.disabled = true;
            return;
        }
        grid.innerHTML = filtered.map((asset) => {
            const isActive = state.selectedIds.has(asset.id);
            return `<button type="button" class="chooser-card ${isActive ? 'is-active' : ''}" data-media-asset-id="${this.escapeHtml(asset.id)}">
                <div class="chooser-card-thumb">${asset.file_url ? `<img src="${this.escapeHtml(asset.file_url)}" alt="${this.escapeHtml(asset.display_name || asset.original_name || 'Media image')}">` : '<i class="fas fa-image"></i>'}</div>
                <div class="chooser-card-body">
                    <div class="chooser-card-title">${this.escapeHtml(asset.display_name || asset.original_name || asset.filename || 'Image')}</div>
                    <div class="chooser-card-meta"><span>${this.escapeHtml(asset.category_name || 'Media')}</span><span>${this.formatFileSize(asset.file_size || 0)}</span></div>
                </div>
            </button>`;
        }).join('');
        if (confirmBtn) confirmBtn.disabled = state.selectedIds.size === 0;
        grid.querySelectorAll('[data-media-asset-id]').forEach((button) => {
            button.addEventListener('click', () => {
                const assetId = button.getAttribute('data-media-asset-id') || '';
                if (!assetId) return;
                if (state.multiple) {
                    if (state.selectedIds.has(assetId)) state.selectedIds.delete(assetId);
                    else state.selectedIds.add(assetId);
                    this.renderMediaLibraryPicker();
                    return;
                }
                state.selectedIds = new Set([assetId]);
                this.renderMediaLibraryPicker();
            });
        });
    };

    KomaginAdmin.prototype.commitMediaLibrarySelection = function() {
        const state = this._mediaLibraryPickerState || {};
        const selected = (this._mediaLibraryAssetsCache || []).filter((asset) => state.selectedIds?.has(asset.id));
        if (!selected.length || typeof state.onConfirm !== 'function') return;
        state.onConfirm(state.multiple ? selected : selected[0]);
        this.hideModal('mediaLibraryModal');
    };

    KomaginAdmin.prototype.decorateImageUploadContainers = function(scope = document) {
        this.ensureChooserUi();
        const fileInputs = Array.from(scope.querySelectorAll('input[type="file"][accept*="image"]'));
        fileInputs.forEach((input) => {
            if (input.dataset.mediaLibraryEnhanced === '1') return;
            if (input.id === 'socialMediaInput') return;
            const formGroup = input.closest('.form-group') || input.parentElement;
            if (!formGroup) return;
            const preview = this.ensureImagePreviewBlock(input);
            const hidden = this.findLinkedHiddenInput(input);
            const existingNote = formGroup.querySelector('[data-media-library-note]');
            const note = existingNote || (() => {
                const small = document.createElement('small');
                small.className = 'media-library-inline-note';
                small.setAttribute('data-media-library-note', '1');
                if (input.name === 'featured_image' && input.dataset.existingPath) {
                    small.textContent = `${input.dataset.existingPath.split('/').pop()} is currently linked. Upload a new file or choose one from the media library.`;
                }
                formGroup.appendChild(small);
                return small;
            })();

            if (input.name === 'featured_image' && !hidden) {
                const generatedHidden = document.createElement('input');
                generatedHidden.type = 'hidden';
                generatedHidden.name = 'featured_image_path';
                generatedHidden.value = input.dataset.existingPath || '';
                input.insertAdjacentElement('afterend', generatedHidden);
            }

            if (input.name === 'featured_image' && input.dataset.existingUrl && preview && !preview.classList.contains('has-image')) {
                preview.innerHTML = `<img src="${this.escapeHtml(input.dataset.existingUrl)}" alt="Featured image">`;
                preview.classList.add('has-image');
            }

            const actions = formGroup.querySelector('.image-upload-actions') || (() => {
                const host = document.createElement('div');
                host.className = 'image-upload-actions';
                const existingButtons = Array.from(formGroup.querySelectorAll('button')).filter((button) => button.closest('.form-actions') === null);
                const anchor = existingButtons[existingButtons.length - 1];
                if (anchor) {
                    anchor.insertAdjacentElement('afterend', host);
                } else {
                    formGroup.appendChild(host);
                }
                return host;
            })();

            const mediaButton = document.createElement('button');
            mediaButton.type = 'button';
            mediaButton.className = 'btn btn-outline';
            mediaButton.innerHTML = '<i class="fas fa-photo-video"></i> Media Library';
            mediaButton.addEventListener('click', () => {
                if (input.id === 'projectGalleryInput') {
                    this.openMediaLibraryPicker({
                        title: 'Choose Project Gallery Images',
                        multiple: true,
                        onConfirm: (assets) => {
                            const paths = assets.map((asset) => asset.file_path).filter(Boolean);
                            this.projectGalleryImages = Array.from(new Set([...(Array.isArray(this.projectGalleryImages) ? this.projectGalleryImages : []), ...paths]));
                            this.renderProjectGalleryManager();
                            this.showSuccess(`${paths.length} gallery image${paths.length === 1 ? '' : 's'} added from the media library`);
                        }
                    });
                    return;
                }
                if (input.id === 'heroSlideImageInput') {
                    this.openMediaLibraryPicker({
                        title: 'Choose Hero Slideshow Images',
                        multiple: true,
                        onConfirm: (assets) => {
                            const incoming = assets.map((asset) => asset.file_path).filter(Boolean);
                            const current = Array.isArray(this.heroSlideImages) ? this.heroSlideImages : [];
                            const combined = Array.from(new Set([...current, ...incoming])).slice(0, 5);
                            this.heroSlideImages = combined;
                            this.renderHeroSlideImages();
                            this.showSuccess('Hero slideshow images updated from the media library');
                        }
                    });
                    return;
                }
                this.openMediaLibraryPicker({
                    title: `Choose ${formGroup.querySelector('label')?.textContent?.trim() || 'Image'}`,
                    onConfirm: (asset) => {
                        const targetHidden = this.findLinkedHiddenInput(input);
                        this.applyMediaLibrarySelection(targetHidden, preview, asset, note);
                        if (input.name === 'featured_image') input.value = '';
                        this.showSuccess('Image selected from the media library');
                    }
                });
            });
            actions.appendChild(mediaButton);
            input.dataset.mediaLibraryEnhanced = '1';
        });
    };

    KomaginAdmin.prototype.setIconFieldValue = function(input, icon) {
        if (!input) return;
        input.value = icon;
        if (input.id === 'serviceIcon') {
            document.querySelectorAll('#serviceIconPicker .icon-option').forEach((option) => {
                option.classList.toggle('active', option.dataset.icon === icon);
            });
        }
        const formGroup = input.closest('.form-group');
        const note = formGroup?.querySelector('[data-icon-current]');
        if (note) {
            note.innerHTML = `<i class="fas ${this.escapeHtml(icon)}"></i> ${this.escapeHtml(prettyIconLabel(icon))}`;
        }
    };

    KomaginAdmin.prototype.openIconLibraryPicker = function(options = {}) {
        this.ensureChooserUi();
        this._iconLibraryPickerState = {
            input: options.input || null,
            currentValue: String(options.currentValue || options.input?.value || 'fa-cog').trim() || 'fa-cog'
        };
        document.getElementById('iconLibraryModalTitle').textContent = options.title || 'Choose an Icon';
        const searchInput = document.getElementById('iconLibrarySearchInput');
        if (searchInput) searchInput.value = '';
        this.renderIconLibraryPicker();
        this.showModal('iconLibraryModal');
    };

    KomaginAdmin.prototype.renderIconLibraryPicker = function() {
        const grid = document.getElementById('iconLibraryGrid');
        if (!grid) return;
        const state = this._iconLibraryPickerState || {};
        const currentValue = String(state.currentValue || '').trim();
        const search = String(document.getElementById('iconLibrarySearchInput')?.value || '').trim().toLowerCase();
        const filtered = ICON_LIBRARY.filter((icon) => {
            if (!search) return true;
            return `${icon} ${prettyIconLabel(icon)}`.toLowerCase().includes(search);
        });
        if (!filtered.length) {
            grid.innerHTML = `<div class="chooser-empty" style="grid-column:1/-1;"><i class="fas fa-icons"></i><p>No icons matched your search.</p></div>`;
            return;
        }
        grid.innerHTML = filtered.map((icon) => `<button type="button" class="icon-library-card ${icon === currentValue ? 'is-active' : ''}" data-icon-choice="${this.escapeHtml(icon)}"><i class="fas ${this.escapeHtml(icon)}"></i><strong>${this.escapeHtml(prettyIconLabel(icon))}</strong><span>${this.escapeHtml(icon)}</span></button>`).join('');
        grid.querySelectorAll('[data-icon-choice]').forEach((button) => {
            button.addEventListener('click', () => {
                const icon = button.getAttribute('data-icon-choice') || 'fa-cog';
                this.setIconFieldValue(state.input, icon);
                this.hideModal('iconLibraryModal');
            });
        });
    };

    KomaginAdmin.prototype.bindServiceIconPicker = function() {
        const serviceIcon = document.getElementById('serviceIcon');
        const picker = document.getElementById('serviceIconPicker');
        if (!serviceIcon || !picker) return;
        picker.querySelectorAll('.icon-option').forEach((option) => {
            if (option.dataset.iconBound === '1') return;
            option.dataset.iconBound = '1';
            option.addEventListener('click', () => this.setIconFieldValue(serviceIcon, option.dataset.icon || 'fa-cog'));
        });
        const group = picker.closest('.form-group');
        if (group && !group.querySelector('[data-service-full-icon-picker]')) {
            const current = document.createElement('small');
            current.className = 'media-library-inline-note';
            current.setAttribute('data-icon-current', '1');
            current.innerHTML = `<i class="fas ${this.escapeHtml(serviceIcon.value || 'fa-cog')}"></i> ${this.escapeHtml(prettyIconLabel(serviceIcon.value || 'fa-cog'))}`;
            group.appendChild(current);

            const actions = document.createElement('div');
            actions.className = 'icon-input-actions';
            actions.innerHTML = `<button type="button" class="btn btn-outline" data-service-full-icon-picker="1"><i class="fas fa-icons"></i> Browse Full Icon Library</button>`;
            group.appendChild(actions);
            actions.querySelector('button')?.addEventListener('click', () => {
                this.openIconLibraryPicker({
                    input: serviceIcon,
                    currentValue: serviceIcon.value || 'fa-cog',
                    title: 'Choose Service Icon'
                });
            });
        }
    };

    KomaginAdmin.prototype.enhanceIconInputs = function(scope = document) {
        this.ensureChooserUi();
        Array.from(scope.querySelectorAll('input[name="icon"]')).forEach((input) => {
            if (input.dataset.iconLibraryEnhanced === '1') return;
            const formGroup = input.closest('.form-group');
            if (!formGroup) return;
            const note = document.createElement('small');
            note.className = 'media-library-inline-note';
            note.setAttribute('data-icon-current', '1');
            note.innerHTML = `<i class="fas ${this.escapeHtml(input.value || 'fa-cog')}"></i> ${this.escapeHtml(prettyIconLabel(input.value || 'fa-cog'))}`;
            const actions = document.createElement('div');
            actions.className = 'icon-input-actions';
            actions.innerHTML = `<button type="button" class="btn btn-outline"><i class="fas fa-icons"></i> Browse Icons</button>`;
            actions.querySelector('button')?.addEventListener('click', () => {
                this.openIconLibraryPicker({
                    input,
                    currentValue: input.value || 'fa-cog',
                    title: `Choose ${formGroup.querySelector('label')?.textContent?.trim() || 'Icon'}`
                });
            });
            input.insertAdjacentElement('afterend', actions);
            actions.insertAdjacentElement('afterend', note);
            input.dataset.iconLibraryEnhanced = '1';
            input.addEventListener('input', () => this.setIconFieldValue(input, input.value || 'fa-cog'));
        });
        this.bindServiceIconPicker();
    };

    const oldShowModal = KomaginAdmin.prototype.showModal;
    KomaginAdmin.prototype.showModal = function(modalId) {
        oldShowModal.call(this, modalId);
        const modal = document.getElementById(modalId);
        if (modal) {
            this.decorateImageUploadContainers(modal);
            this.enhanceIconInputs(modal);
        }
    };

    const oldInitialize = KomaginAdmin.prototype.initializeEventListeners;
    KomaginAdmin.prototype.initializeEventListeners = function() {
        oldInitialize.call(this);
        this.ensureChooserUi();
        this.decorateImageUploadContainers(document);
        this.enhanceIconInputs(document);
    };

    const oldOpenBlogPostForm = KomaginAdmin.prototype.openBlogPostForm;
    KomaginAdmin.prototype.openBlogPostForm = function(id = null) {
        oldOpenBlogPostForm.call(this, id);
        const post = id ? ((this._blogPosts || []).find((item) => item.id === id) || {}) : {};
        const featuredInput = document.querySelector('#blogPostForm input[name="featured_image"]');
        if (featuredInput) {
            featuredInput.dataset.existingPath = post.featured_image || '';
            featuredInput.dataset.existingUrl = post.featured_image_url || '';
            const hidden = this.findLinkedHiddenInput(featuredInput);
            if (hidden) hidden.value = post.featured_image || '';
            const preview = this.ensureImagePreviewBlock(featuredInput);
            if (preview && post.featured_image_url) {
                preview.innerHTML = `<img src="${this.escapeHtml(post.featured_image_url)}" alt="Featured image">`;
                preview.classList.add('has-image');
            }
            const note = featuredInput.closest('.form-group')?.querySelector('[data-media-library-note]');
            if (note && post.featured_image) {
                note.textContent = `${post.featured_image.split('/').pop()} is currently linked. Upload a new file or choose one from the media library.`;
            }
        }
        this.decorateImageUploadContainers(document.getElementById('upgradeModal'));
    };
})();

// JOBS DASHBOARD LATE OVERRIDE: keep safer JSON reads after every later admin patch has loaded.
(function() {
    if (!window.KomaginAdmin || KomaginAdmin.prototype.__jobsDashboardLateOverride) return;
    KomaginAdmin.prototype.__jobsDashboardLateOverride = true;

    KomaginAdmin.prototype.loadRecentApplications = async function() {
        const container = document.getElementById('recentApplications');
        if (!container) return;
        try {
            const result = await this.readAdminJsonResponse(
                fetch(`${this.apiBase}?action=hr_get_applications&status=all`),
                'Recent applications could not be loaded'
            );
            if (!result.success) throw new Error(result.error || 'Recent applications could not be loaded');
            const rows = (result.data || []).slice(0, 5);
            if (!rows.length) {
                container.innerHTML = `<div class="empty-state"><i class="fas fa-file-signature"></i><p>No applications received yet.</p></div>`;
                return;
            }
            container.innerHTML = rows.map(app => `<div class="activity-item">
                <div class="activity-content">
                    <h4>${this.escapeHtml(app.applicant_name || 'Applicant')}</h4>
                    <p>${this.escapeHtml(app.job_title || 'Vacancy')} <span class="status-badge ${this.getApplicationStatusClass(app.status || 'received')}">${this.escapeHtml(this.label(app.status || 'received'))}</span></p>
                    <small>${app.created_at ? this.formatDate(app.created_at) : ''}</small>
                </div>
            </div>`).join('');
        } catch (error) {
            container.innerHTML = `<div class="empty-state"><i class="fas fa-triangle-exclamation"></i><p>${this.escapeHtml(error.message)}</p></div>`;
        }
    };

    KomaginAdmin.prototype.refreshDashboardDataFeeds = async function() {
        const safeRead = async (url) => {
            try {
                return await this.readAdminJsonResponse(fetch(url), 'Dashboard data could not be loaded');
            } catch (error) {
                return { success: false, data: [] };
            }
        };
        const [
            projectsResult, servicesResult, testimonialsResult, teamResult,
            contactsResult, subscribersResult,
            jobsResult, documentsResult, hireResult, applicationsResult,
            platformsResult
        ] = await Promise.all([
            safeRead(`${this.apiBase}?action=get_projects`),
            safeRead(`${this.apiBase}?action=get_services`),
            safeRead(`${this.apiBase}?action=get_testimonials`),
            safeRead(`${this.apiBase}?action=get_team`),
            safeRead(`${this.apiBase}?action=get_contacts`),
            safeRead(`${this.apiBase}?action=get_subscribers`),
            safeRead(`${this.apiBase}?action=hr_get_jobs&status=published`),
            safeRead(`${this.apiBase}?action=documents_get_all`),
            safeRead(`${this.apiBase}?action=hire_items_get_all`),
            safeRead(`${this.apiBase}?action=hr_get_applications&status=all`),
            safeRead(`${this.apiBase}?action=social_get_platforms`)
        ]);
        this.dashboardProjects     = projectsResult.success     ? (projectsResult.data     || []) : (this.dashboardProjects     || []);
        this.dashboardServices     = servicesResult.success     ? (servicesResult.data     || []) : (this.dashboardServices     || []);
        this.dashboardTestimonials = testimonialsResult.success ? (testimonialsResult.data || []) : (this.dashboardTestimonials || []);
        this.dashboardTeam         = teamResult.success         ? (teamResult.data         || []) : (this.dashboardTeam         || []);
        this.dashboardContacts     = contactsResult.success     ? (contactsResult.data     || []) : (this.dashboardContacts     || []);
        this.dashboardSubscribers  = subscribersResult.success  ? (subscribersResult.data  || []) : (this.dashboardSubscribers  || []);
        this.dashboardJobs         = jobsResult.success         ? (jobsResult.data         || []) : (this.dashboardJobs         || []);
        this.dashboardDocuments    = documentsResult.success    ? (documentsResult.data    || []) : (this.dashboardDocuments    || []);
        this.dashboardHireItems    = hireResult.success         ? (hireResult.data         || []) : (this.dashboardHireItems    || []);
        this.dashboardApplications = applicationsResult.success ? (applicationsResult.data || []) : (this.dashboardApplications || []);
        this.dashboardPlatforms    = platformsResult.success    ? (platformsResult.data    || []) : (this.dashboardPlatforms    || []);
    };
})();
