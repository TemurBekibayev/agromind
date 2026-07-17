import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'dart:async';
import 'dart:io';
import '../providers/providers.dart';
import 'private_chat_screen.dart';

class ChatScreen extends ConsumerStatefulWidget {
  const ChatScreen({super.key});

  @override
  ConsumerState<ChatScreen> createState() => _ChatScreenState();
}

class _ChatScreenState extends ConsumerState<ChatScreen> with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final TextEditingController _messageController = TextEditingController();
  final ScrollController _groupScrollController = ScrollController();
  final ScrollController _adminScrollController = ScrollController();

  bool _isEditing = false;
  int? _editingMessageId;

  // Real Admin Support Chat state
  Map<String, dynamic>? _adminUser;
  bool _isLoadingAdminUser = true;
  String? _adminLoadError;

  // Admin Direct Chat Simulation State (keep as fallback)
  final List<Map<String, dynamic>> _adminMessages = [
    {
      'id': 1001,
      'message': 'Assalomu alaykum! AgroMind qo‘llab-quvvatlash bo‘limiga xush kelibsiz. Qanday yordam bera olaman?',
      'created_at': DateTime.now().subtract(const Duration(minutes: 30)).toIso8601String(),
      'is_me': false,
    }
  ];

  int _groupUnread = 0;
  int _privateUnread = 0;
  int _adminUnread = 0;

  bool _showSendButton = false;

  // Voice recording states
  bool _isRecording = false;
  int _recordSeconds = 0;
  Timer? _recordTimer;

  // Voice playing states
  int? _playingMessageId;
  double _playProgress = 0.0;
  Timer? _playTimer;
  int _playingSeconds = 0;

  // Real database private chats state
  List<Map<String, dynamic>> _privateChats = [];
  bool _isLoadingPrivateChats = true;

  Future<void> _loadAdminUser() async {
    if (!mounted) return;
    setState(() {
      _isLoadingAdminUser = true;
      _adminLoadError = null;
    });
    try {
      final api = ref.read(apiServiceProvider);
      final res = await api.getAdminUser();
      if (res.data['status'] == 'success' && mounted) {
        setState(() {
          _adminUser = res.data['admin'];
          _isLoadingAdminUser = false;
        });
      } else {
        setState(() {
          _adminLoadError = 'Admin ma\'lumotlarini yuklab bo\'lmadi.';
          _isLoadingAdminUser = false;
        });
      }
    } catch (e) {
      if (mounted) {
        setState(() {
          _adminLoadError = 'Tarmoq xatosi: Admin ma\'lumotlarini yuklab bo\'lmadi.';
          _isLoadingAdminUser = false;
        });
      }
    }
  }

  @override
  void initState() {
    super.initState();
    _messageController.addListener(_onTextChanged);
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(() {
      if (_tabController.indexIsChanging) {
        setState(() {
          if (_tabController.index == 0) {
            _groupUnread = 0;
          } else if (_tabController.index == 1) {
            _privateUnread = 0;
            _fetchPrivateChats();
          } else if (_tabController.index == 2) {
            _adminUnread = 0;
          }
        });
      }
    });
    
    // Fetch initial chat messages after screen binds
    WidgetsBinding.instance.addPostFrameCallback((_) {
      final authState = ref.read(authProvider);
      final userDistrict = authState.user?['district']?.toString() ?? 'Amudaryo tumani';
      ref.read(chatMessagesProvider.notifier).fetchMessages(district: userDistrict);
      _fetchPrivateChats();
      _loadAdminUser();
    });
  }

  Future<void> _fetchPrivateChats() async {
    if (!mounted) return;
    setState(() {
      _isLoadingPrivateChats = true;
    });

    final api = ref.read(apiServiceProvider);
    try {
      final res = await api.getPrivateChatUsers();
      if (res.data['status'] == 'success' && mounted) {
        final List<dynamic> users = res.data['users'];
        int totalUnread = 0;
        final list = users.map((u) {
          final map = u as Map<String, dynamic>;
          totalUnread += int.tryParse('${map['unread_count']}') ?? 0;
          return map;
        }).toList();

        setState(() {
          _privateChats = list;
          _privateUnread = totalUnread;
          _isLoadingPrivateChats = false;
        });
      }
    } catch (_) {
      if (mounted) {
        setState(() {
          _isLoadingPrivateChats = false;
        });
      }
    }
  }

  void _onTextChanged() {
    setState(() {
      _showSendButton = _messageController.text.trim().isNotEmpty;
    });
  }

  @override
  void dispose() {
    _messageController.removeListener(_onTextChanged);
    _tabController.dispose();
    _messageController.dispose();
    _groupScrollController.dispose();
    _adminScrollController.dispose();
    _recordTimer?.cancel();
    _playTimer?.cancel();
    super.dispose();
  }

  // Voice playback logic
  void _playVoice(int msgId, int duration) {
    if (_playingMessageId == msgId) {
      _playTimer?.cancel();
      setState(() {
        _playingMessageId = null;
        _playProgress = 0.0;
        _playingSeconds = 0;
      });
      return;
    }

    _playTimer?.cancel();
    setState(() {
      _playingMessageId = msgId;
      _playProgress = 0.0;
      _playingSeconds = 0;
    });

    _playTimer = Timer.periodic(const Duration(milliseconds: 200), (timer) {
      if (!mounted) return;
      setState(() {
        _playProgress += 0.2 / duration;
        _playingSeconds = (_playProgress * duration).toInt();
        if (_playProgress >= 1.0) {
          _playTimer?.cancel();
          _playingMessageId = null;
          _playProgress = 0.0;
          _playingSeconds = 0;
        }
      });
    });
  }

  // Recording simulation logic
  void _startRecording() {
    setState(() {
      _isRecording = true;
      _recordSeconds = 0;
    });
    _recordTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (!mounted) return;
      setState(() {
        _recordSeconds++;
      });
    });
  }

  void _cancelRecording() {
    _recordTimer?.cancel();
    setState(() {
      _isRecording = false;
      _recordSeconds = 0;
    });
  }

  void _stopAndSendVoice(bool isAdmin, String? userDistrict) {
    _recordTimer?.cancel();
    final duration = _recordSeconds > 0 ? _recordSeconds : 3;
    setState(() {
      _isRecording = false;
      _recordSeconds = 0;
    });

    if (isAdmin) {
      if (_adminUser != null) {
        final adminId = _adminUser!['id'] as int;
        try {
          final tempDir = Directory.systemTemp;
          final file = File('${tempDir.path}/voice_message_${DateTime.now().millisecondsSinceEpoch}.mp3');
          file.writeAsBytesSync(List.generate(100, (index) => index % 256));
          ref.read(privateMessagesProvider(adminId).notifier).sendMessage(audioPath: file.path);
          Future.delayed(const Duration(milliseconds: 100), () => _scrollToBottom(_adminScrollController));
        } catch (_) {}
      } else {
        setState(() {
          _adminMessages.add({
            'id': DateTime.now().millisecondsSinceEpoch,
            'message': '',
            'is_voice': true,
            'voice_duration': duration,
            'created_at': DateTime.now().toIso8601String(),
            'is_me': true,
          });
        });
        Future.delayed(const Duration(milliseconds: 100), () => _scrollToBottom(_adminScrollController));
        
        // Admin simulated response
        Future.delayed(const Duration(seconds: 3), () {
          if (!mounted) return;
          setState(() {
            _adminMessages.add({
              'id': DateTime.now().millisecondsSinceEpoch + 1,
              'message': 'Ovozli xabaringizni eshitdim. Mutaxassislarimiz tez fursatda siz bilan bog\'lanishadi.',
              'created_at': DateTime.now().toIso8601String(),
              'is_me': false,
              'is_voice': false,
            });
          });
          _scrollToBottom(_adminScrollController);
        });
      }
    } else {
      // Group voice simulation
      ref.read(chatMessagesProvider.notifier).state.whenData((currentList) {
        final voiceMsg = {
          'id': DateTime.now().millisecondsSinceEpoch,
          'message': '',
          'is_voice': true,
          'voice_duration': duration,
          'created_at': DateTime.now().toIso8601String(),
          'user': {'id': ref.read(authProvider).user?['id'], 'name': 'Men (Fermer)', 'district': userDistrict},
        };
        ref.read(chatMessagesProvider.notifier).state = AsyncValue.data([...currentList, voiceMsg]);
        Future.delayed(const Duration(milliseconds: 100), () => _scrollToBottom(_groupScrollController));
      });
    }
  }

  void _scrollToBottom(ScrollController controller) {
    if (controller.hasClients) {
      controller.animateTo(
        controller.position.maxScrollExtent,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  String _formatTime(String? createdAt) {
    if (createdAt == null) return '';
    try {
      final dt = DateTime.parse(createdAt).toLocal();
      return DateFormat('HH:mm').format(dt);
    } catch (e) {
      return '';
    }
  }

  // --- Message Actions (Send, Edit, Delete) ---

  Future<void> _sendMessage(String? userDistrict) async {
    final text = _messageController.text.trim();
    if (text.isEmpty) return;

    if (_isEditing && _editingMessageId != null) {
      final msgId = _editingMessageId!;
      setState(() {
        _isEditing = false;
        _editingMessageId = null;
        _messageController.clear();
      });
      await ref.read(chatMessagesProvider.notifier).editMessage(msgId, text);
    } else {
      _messageController.clear();
      final success = await ref.read(chatMessagesProvider.notifier).sendMessage(text);
      if (success) {
        Future.delayed(const Duration(milliseconds: 100), () => _scrollToBottom(_groupScrollController));
      }
    }
  }

  void _showMessageOptions(Map<String, dynamic> msg, bool isMe) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              if (isMe) ...[
                ListTile(
                  leading: const Icon(Icons.edit_rounded, color: Colors.blue),
                  title: const Text('Tahrirlash'),
                  onTap: () {
                    Navigator.pop(context);
                    setState(() {
                      _isEditing = true;
                      _editingMessageId = msg['id'];
                      _messageController.text = msg['message'] ?? '';
                    });
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.delete_forever_rounded, color: Colors.red),
                  title: const Text('Xabarni o\'chirish'),
                  onTap: () {
                    Navigator.pop(context);
                    _confirmDelete(msg['id']);
                  },
                ),
              ] else ...[
                ListTile(
                  leading: const Icon(Icons.copy_rounded),
                  title: const Text('Matnni nusxalash'),
                  onTap: () {
                    Navigator.pop(context);
                    Clipboard.setData(ClipboardData(text: msg['message'] ?? ''));
                    ScaffoldMessenger.of(context).showSnackBar(
                      const SnackBar(content: Text('Xabar nusxalandi!')),
                    );
                  },
                ),
              ],
            ],
          ),
        );
      },
    );
  }

  void _confirmDelete(int messageId) {
    showDialog(
      context: context,
      builder: (context) => AlertDialog(
        title: const Text('Xabarni o‘chirish'),
        content: const Text('Ushbu xabarni qanday o‘chirmoqchisiz?'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(context),
            child: const Text('Bekor qilish'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              ref.read(chatMessagesProvider.notifier).deleteMessage(messageId, false);
            },
            child: const Text('Faqat mendan'),
          ),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              ref.read(chatMessagesProvider.notifier).deleteMessage(messageId, true);
            },
            child: const Text('Hammadan o‘chirish', style: TextStyle(color: Colors.red)),
          ),
        ],
      ),
    );
  }

  // --- Admin Chat Logic ---

  Future<void> _sendAdminMessage() async {
    final text = _messageController.text.trim();
    if (text.isEmpty) return;

    if (_adminUser != null) {
      final adminId = _adminUser!['id'] as int;
      _messageController.clear();
      final success = await ref
          .read(privateMessagesProvider(adminId).notifier)
          .sendMessage(message: text);

      if (success) {
        Future.delayed(const Duration(milliseconds: 100), () => _scrollToBottom(_adminScrollController));
      } else {
        if (mounted) {
          ScaffoldMessenger.of(context).showSnackBar(
            const SnackBar(content: Text('Xabarni yuborishda xatolik yuz berdi.'), backgroundColor: Colors.red),
          );
        }
      }
    } else {
      setState(() {
        _adminMessages.add({
          'id': DateTime.now().millisecondsSinceEpoch,
          'message': text,
          'created_at': DateTime.now().toIso8601String(),
          'is_me': true,
        });
        _messageController.clear();
      });

      Future.delayed(const Duration(milliseconds: 100), () => _scrollToBottom(_adminScrollController));

      // Admin response simulation
      Future.delayed(const Duration(seconds: 2), () {
        if (!mounted) return;
        setState(() {
          _adminMessages.add({
            'id': DateTime.now().millisecondsSinceEpoch + 1,
            'message': 'Akbar sizning xabaringizni qabul qildi. Tez orada javob qaytaradi.',
            'created_at': DateTime.now().toIso8601String(),
            'is_me': false,
          });
        });
        _scrollToBottom(_adminScrollController);
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    final currentAuth = ref.watch(authProvider);
    final userDistrict = currentAuth.user?['district']?.toString() ?? 'Amudaryo tumani';
    final chatState = ref.watch(chatMessagesProvider);
    final currentUserId = currentAuth.user?['id'];
    
    final adminMessagesState = _adminUser != null
        ? ref.watch(privateMessagesProvider(_adminUser!['id'] as int))
        : null;

    // Auto-scroll logic for group
    WidgetsBinding.instance.addPostFrameCallback((_) {
      if (_groupScrollController.hasClients && !_isEditing) {
        _groupScrollController.jumpTo(_groupScrollController.position.maxScrollExtent);
      }
    });

    return Scaffold(
      appBar: AppBar(
        backgroundColor: Theme.of(context).colorScheme.primary,
        foregroundColor: Theme.of(context).colorScheme.onPrimary,
        elevation: 1,
        title: const Text(
          'AgroMind Suhbatlar',
          style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold),
        ),
        bottom: TabBar(
          controller: _tabController,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white60,
          indicatorColor: Colors.white,
          indicatorWeight: 3,
          tabs: [
            Tab(text: ('$userDistrict Guruhi'.split(' ').first + ' guruh') + (_groupUnread > 0 ? ' (+$_groupUnread)' : '')),
            Tab(text: 'Shaxsiy' + (_privateUnread > 0 ? ' (+$_privateUnread)' : '')),
            Tab(text: 'Admin' + (_adminUnread > 0 ? ' (+$_adminUnread)' : '')),
          ],
        ),
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh_rounded),
            onPressed: () => ref.read(chatMessagesProvider.notifier).fetchMessages(district: userDistrict),
          ),
        ],
      ),
      body: TabBarView(
        controller: _tabController,
        children: [
          // 1. Group Chat View
          Column(
            children: [
              Expanded(
                child: chatState.when(
                  data: (messages) {
                    if (messages.isEmpty) {
                      return _buildEmptyState('Guruhda xabarlar yo\'q', 'Birinchi bo\'lib xabar yozing!');
                    }
                    return RefreshIndicator(
                      onRefresh: () => ref.read(chatMessagesProvider.notifier).fetchMessages(district: userDistrict),
                      child: ListView.builder(
                        controller: _groupScrollController,
                        padding: const EdgeInsets.all(16.0),
                        physics: const AlwaysScrollableScrollPhysics(),
                        itemCount: messages.length,
                        itemBuilder: (context, index) {
                          final msg = messages[index];
                          final user = msg['user'] ?? {};
                          final senderId = user['id'];
                          final isMe = senderId == currentUserId;
                          final senderName = user['name'] ?? 'Fermer';
                          final isEdited = msg['is_edited'] ?? false;
                          
                          String senderDisplayName = senderName;
                          if (!isMe) {
                            final Map<String, String> mockFarms = {
                              'Rustam aka': 'Obi hayot F.X.',
                              'Shavkat fermer': 'Yurt rizqi F.X.',
                              'Mirzaolim ota': 'Bobodehqon F.X.',
                            };
                            final farm = user['farm_name'] ?? mockFarms[senderName] ?? 'Fermer xo‘jaligi';
                            senderDisplayName = "$farm ($senderName)";
                          }
                          
                          return Align(
                            alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
                            child: GestureDetector(
                              onLongPress: () => _showMessageOptions(msg, isMe),
                              child: Container(
                                margin: const EdgeInsets.only(bottom: 12.0),
                                constraints: BoxConstraints(
                                  maxWidth: MediaQuery.of(context).size.width * 0.75,
                                ),
                                padding: const EdgeInsets.all(12.0),
                                decoration: BoxDecoration(
                                  color: isMe ? const Color(0xFF1A3C2A) : Colors.white,
                                  borderRadius: BorderRadius.only(
                                    topLeft: const Radius.circular(16),
                                    topRight: const Radius.circular(16),
                                    bottomLeft: isMe ? const Radius.circular(16) : Radius.zero,
                                    bottomRight: isMe ? Radius.zero : const Radius.circular(16),
                                  ),
                                  boxShadow: [
                                    BoxShadow(
                                      color: Colors.black.withOpacity(0.04),
                                      blurRadius: 4,
                                      offset: const Offset(0, 2),
                                    )
                                  ],
                                ),
                                child: msg['is_voice'] == true
                                    ? _buildVoiceBubbleContent(msg, isMe)
                                    : Column(
                                        crossAxisAlignment: CrossAxisAlignment.start,
                                        mainAxisSize: MainAxisSize.min,
                                        children: [
                                          if (!isMe)
                                            Text(
                                              senderDisplayName,
                                              style: const TextStyle(
                                                fontSize: 12,
                                                fontWeight: FontWeight.bold,
                                                color: Color(0xFF2E6F40),
                                              ),
                                            ),
                                          const SizedBox(height: 4),
                                          Text(
                                            msg['message'] ?? '',
                                            style: TextStyle(
                                              fontSize: 14,
                                              color: isMe ? Colors.white : Colors.black87,
                                            ),
                                          ),
                                          const SizedBox(height: 4),
                                          Row(
                                            mainAxisAlignment: MainAxisAlignment.end,
                                            mainAxisSize: MainAxisSize.min,
                                            children: [
                                              if (isEdited) ...[
                                                Text(
                                                  'tahrirlangan ',
                                                  style: TextStyle(
                                                    fontSize: 9,
                                                    fontStyle: FontStyle.italic,
                                                    color: isMe ? Colors.white70 : Colors.black38,
                                                  ),
                                                ),
                                              ],
                                              Text(
                                                _formatTime(msg['created_at']),
                                                style: TextStyle(
                                                  fontSize: 9,
                                                  color: isMe ? Colors.white70 : Colors.black38,
                                                ),
                                              ),
                                            ],
                                          ),
                                        ],
                                      ),
                              ),
                            ),
                          );
                        },
                      ),
                    );
                  },
                  error: (e, __) => _buildErrorState(),
                  loading: () => _buildLoading(),
                ),
              ),
              _buildInputArea(onSend: () => _sendMessage(userDistrict)),
            ],
          ),

          // 2. Personal Chats List (Real Database)
          _isLoadingPrivateChats
              ? const Center(child: CircularProgressIndicator(color: Color(0xFF1A3C2A)))
              : RefreshIndicator(
                  onRefresh: _fetchPrivateChats,
                  child: ListView(
                    padding: const EdgeInsets.all(8.0),
                    physics: const AlwaysScrollableScrollPhysics(),
                    children: [
                      ListTile(
                        leading: CircleAvatar(
                          backgroundColor: const Color(0xFF1A3C2A).withOpacity(0.1),
                          child: const Icon(Icons.person_add_rounded, color: Color(0xFF1A3C2A)),
                        ),
                        title: const Text('Yangi suhbat boshlash', style: TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A))),
                        subtitle: const Text('Tumaningizdagi fermerlar ro‘yxati'),
                        onTap: () {
                          _showStartNewChatDialog();
                        },
                      ),
                      const Divider(height: 1),
                      const SizedBox(height: 4),
                      if (_privateChats.isEmpty)
                        Padding(
                          padding: const EdgeInsets.only(top: 80.0),
                          child: _buildEmptyState(
                            'Suhbatlar topilmadi',
                            'Muloqot boshlash uchun yuqoridagi "Yangi suhbat boshlash" tugmasini bosing!',
                          ),
                        )
                      else
                        ..._privateChats.map((u) {
                          final lastTime = u['last_message_time'] != null
                              ? _formatTime(u['last_message_time'])
                              : '';
                          return _buildPrivateChatTile(
                            id: u['id'],
                            name: u['name'],
                            lastMsg: u['last_message'] ?? '',
                            time: lastTime,
                            unreadCount: u['unread_count'] ?? 0,
                          );
                        }),
                    ],
                  ),
                ),

          // 3. Admin Support Page
          _isLoadingAdminUser
              ? const Center(child: CircularProgressIndicator(color: Color(0xFF1A3C2A)))
              : _adminUser == null
                  ? Center(
                      child: Padding(
                        padding: const EdgeInsets.all(24.0),
                        child: Column(
                          mainAxisAlignment: MainAxisAlignment.center,
                          children: [
                            Text(_adminLoadError ?? 'Admin bilan bog‘lanib bo‘lmadi.'),
                            const SizedBox(height: 12),
                            ElevatedButton(
                              onPressed: _loadAdminUser,
                              child: const Text('Qayta urinish'),
                            ),
                          ],
                        ),
                      ),
                    )
                  : Column(
                      children: [
                        // Header with 24/7 online status
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                          decoration: BoxDecoration(
                            color: const Color(0xFFE8F5E9),
                            border: Border(bottom: BorderSide(color: Colors.grey[200]!)),
                          ),
                          child: Row(
                            children: [
                              Stack(
                                children: [
                                  CircleAvatar(
                                    backgroundColor: const Color(0xFF1A3C2A).withOpacity(0.1),
                                    child: const Icon(Icons.support_agent_rounded, color: Color(0xFF1A3C2A)),
                                  ),
                                  Positioned(
                                    right: 0,
                                    bottom: 0,
                                    child: Container(
                                      width: 12,
                                      height: 12,
                                      decoration: BoxDecoration(
                                        color: Colors.green,
                                        shape: BoxShape.circle,
                                        border: Border.all(color: Colors.white, width: 2),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: Column(
                                  crossAxisAlignment: CrossAxisAlignment.start,
                                  children: [
                                    Text(
                                      _adminUser!['name'] ?? 'AgroMind Qo‘llab-quvvatlash',
                                      style: const TextStyle(fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A), fontSize: 15),
                                    ),
                                    Row(
                                      children: [
                                        Container(
                                          width: 8,
                                          height: 8,
                                          decoration: const BoxDecoration(
                                            color: Colors.green,
                                            shape: BoxShape.circle,
                                          ),
                                        ),
                                        const SizedBox(width: 4),
                                        const Text(
                                          'Qo‘llab-quvvatlash 24/7 onlayn',
                                          style: TextStyle(fontSize: 11, color: Colors.green, fontWeight: FontWeight.bold),
                                        ),
                                      ],
                                    ),
                                  ],
                                ),
                              ),
                            ],
                          ),
                        ),
                        
                        // Messages List
                        Expanded(
                          child: adminMessagesState!.when(
                            data: (messages) {
                              if (messages.isEmpty) {
                                return _buildEmptyState(
                                  'Savolingiz bormi?',
                                  'Muammo yoki takliflaringizni yozib qoldiring. Biz sizga tez orada javob beramiz!',
                                );
                              }
                              // Auto-scroll logic for admin chat
                              WidgetsBinding.instance.addPostFrameCallback((_) {
                                if (_adminScrollController.hasClients) {
                                  _adminScrollController.jumpTo(_adminScrollController.position.maxScrollExtent);
                                }
                              });
                              return RefreshIndicator(
                                onRefresh: () => ref.read(privateMessagesProvider(_adminUser!['id'] as int).notifier).fetchMessages(showLoading: true),
                                child: ListView.builder(
                                  controller: _adminScrollController,
                                  padding: const EdgeInsets.all(16.0),
                                  physics: const AlwaysScrollableScrollPhysics(),
                                  itemCount: messages.length,
                                  itemBuilder: (context, index) {
                                    final msg = messages[index];
                                    final isMe = msg['sender_id'] == currentUserId;
                                    final isRead = msg['is_read'] == true;
                                    final isVoice = msg['is_voice'] == true || msg['audio_path'] != null;

                                    return Align(
                                      alignment: isMe ? Alignment.centerRight : Alignment.centerLeft,
                                      child: Container(
                                        margin: const EdgeInsets.only(bottom: 12.0),
                                        constraints: BoxConstraints(
                                          maxWidth: MediaQuery.of(context).size.width * 0.75,
                                        ),
                                        padding: const EdgeInsets.all(12.0),
                                        decoration: BoxDecoration(
                                          color: isMe ? const Color(0xFF1A3C2A) : Colors.white,
                                          borderRadius: BorderRadius.only(
                                            topLeft: const Radius.circular(16),
                                            topRight: const Radius.circular(16),
                                            bottomLeft: isMe ? const Radius.circular(16) : Radius.zero,
                                            bottomRight: isMe ? Radius.zero : const Radius.circular(16),
                                          ),
                                          boxShadow: [
                                            BoxShadow(
                                              color: Colors.black.withOpacity(0.04),
                                              blurRadius: 4,
                                              offset: const Offset(0, 2),
                                            )
                                          ],
                                        ),
                                        child: isVoice
                                            ? _buildVoiceBubbleContent(msg, isMe)
                                            : Column(
                                                crossAxisAlignment: CrossAxisAlignment.start,
                                                mainAxisSize: MainAxisSize.min,
                                                children: [
                                                  Text(
                                                    msg['message'] ?? '',
                                                    style: TextStyle(
                                                      fontSize: 14,
                                                      color: isMe ? Colors.white : Colors.black87,
                                                    ),
                                                  ),
                                                  const SizedBox(height: 4),
                                                  Row(
                                                    mainAxisAlignment: MainAxisAlignment.end,
                                                    mainAxisSize: MainAxisSize.min,
                                                    children: [
                                                      Text(
                                                        _formatTime(msg['created_at']),
                                                        style: TextStyle(
                                                          fontSize: 9,
                                                          color: isMe ? Colors.white70 : Colors.black38,
                                                        ),
                                                      ),
                                                      if (isMe) ...[
                                                        const SizedBox(width: 4),
                                                        Icon(
                                                          isRead ? Icons.done_all_rounded : Icons.done_rounded,
                                                          size: 13,
                                                          color: isRead ? Colors.lightBlueAccent : Colors.white60,
                                                        ),
                                                      ],
                                                    ],
                                                  ),
                                                ],
                                              ),
                                      ),
                                    );
                                  },
                                ),
                              );
                            },
                            error: (e, __) => Center(
                              child: Column(
                                mainAxisAlignment: MainAxisAlignment.center,
                                children: [
                                  const Text('Suhbatni yuklashda xatolik yuz berdi.', style: TextStyle(color: Colors.red)),
                                  const SizedBox(height: 8),
                                  ElevatedButton(
                                    onPressed: () => ref.read(privateMessagesProvider(_adminUser!['id'] as int).notifier).fetchMessages(showLoading: true),
                                    child: const Text('Qayta urinish'),
                                  ),
                                ],
                              ),
                            ),
                            loading: () => const Center(
                              child: CircularProgressIndicator(color: Color(0xFF1A3C2A)),
                            ),
                          ),
                        ),
                        
                        // Input Area
                        _buildInputArea(onSend: _sendAdminMessage),
                      ],
                    ),
        ],
      ),
    );
  }

  // --- UI Component Helpers ---

  Widget _buildEmptyState(String title, String subtitle) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.chat_bubble_outline_rounded, size: 64, color: Colors.grey[400]),
          const SizedBox(height: 16),
          Text(
            title,
            style: const TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Colors.black54),
          ),
          const SizedBox(height: 8),
          Text(
            subtitle,
            style: const TextStyle(fontSize: 13, color: Colors.black38),
          ),
        ],
      ),
    );
  }

  Widget _buildErrorState() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          const Text('Xabarlarni yuklashda xatolik yuz berdi.', style: TextStyle(color: Colors.red)),
          const SizedBox(height: 8),
          ElevatedButton(
            onPressed: () => ref.read(chatMessagesProvider.notifier).fetchMessages(),
            child: const Text('Qayta urinish'),
          ),
        ],
      ),
    );
  }

  Widget _buildLoading() {
    return const Center(child: CircularProgressIndicator(color: Color(0xFF1A3C2A)));
  }

  Widget _buildVoiceBubbleContent(Map<String, dynamic> msg, bool isMe) {
    final duration = msg['voice_duration'] ?? 3;
    final isPlaying = _playingMessageId == msg['id'];
    final timeStr = _formatTime(msg['created_at']);
    
    return Row(
      mainAxisSize: MainAxisSize.min,
      children: [
        IconButton(
          onPressed: () => _playVoice(msg['id'], duration),
          padding: EdgeInsets.zero,
          constraints: const BoxConstraints(minWidth: 32, minHeight: 32),
          icon: Icon(
            isPlaying ? Icons.pause_circle_filled_rounded : Icons.play_circle_filled_rounded,
            color: isMe ? Colors.greenAccent : const Color(0xFF1A3C2A),
            size: 32,
          ),
        ),
        const SizedBox(width: 8),
        Expanded(
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              SizedBox(
                height: 15,
                child: isPlaying
                    ? LinearProgressIndicator(
                        value: _playProgress,
                        backgroundColor: isMe ? Colors.white24 : Colors.grey[200],
                        valueColor: AlwaysStoppedAnimation<Color>(
                          isMe ? Colors.greenAccent : const Color(0xFF1A3C2A),
                        ),
                      )
                    : Row(
                        mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                        children: List.generate(15, (index) {
                          final h = (index % 3 + 2) * 3.0;
                          return Container(
                            width: 2,
                            height: h,
                            color: isMe ? Colors.white60 : Colors.grey[400],
                          );
                        }),
                      ),
              ),
              const SizedBox(height: 4),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text(
                    isPlaying ? _formatDuration(_playingSeconds) : _formatDuration(duration),
                    style: TextStyle(
                      fontSize: 10,
                      color: isMe ? Colors.white70 : Colors.black45,
                    ),
                  ),
                  Text(
                    timeStr,
                    style: TextStyle(
                      fontSize: 9,
                      color: isMe ? Colors.white70 : Colors.black38,
                    ),
                  ),
                ],
              )
            ],
          ),
        ),
      ],
    );
  }

  String _formatDuration(int seconds) {
    final m = seconds ~/ 60;
    final s = seconds % 60;
    return '$m:${s.toString().padLeft(2, '0')}';
  }

  Widget _buildPrivateChatTile({
    required int id,
    required String name,
    required String lastMsg,
    required String time,
    required int unreadCount,
  }) {
    return Card(
      elevation: 0.5,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      margin: const EdgeInsets.symmetric(vertical: 4.0, horizontal: 8.0),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: Colors.green[100],
          child: Text(name[0], style: const TextStyle(color: Color(0xFF1A3C2A), fontWeight: FontWeight.bold)),
        ),
        title: Text(name, style: const TextStyle(fontWeight: FontWeight.bold)),
        subtitle: Text(lastMsg, maxLines: 1, overflow: TextOverflow.ellipsis),
        trailing: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          crossAxisAlignment: CrossAxisAlignment.end,
          children: [
            Text(time, style: const TextStyle(fontSize: 10, color: Colors.grey)),
            const SizedBox(height: 4),
            if (unreadCount > 0)
              Container(
                padding: const EdgeInsets.all(5),
                decoration: const BoxDecoration(color: Color(0xFF1A3C2A), shape: BoxShape.circle),
                child: Text('$unreadCount', style: const TextStyle(color: Colors.white, fontSize: 8)),
              ),
          ],
        ),
        onTap: () {
          setState(() {
            _privateUnread = 0;
          });
          Navigator.push(
            context,
            MaterialPageRoute(
              builder: (context) => PrivateChatScreen(partner: {'id': id, 'name': name}),
            ),
          ).then((_) => _fetchPrivateChats());
        },
      ),
    );
  }

  void _openAdminChat() async {
    final api = ref.read(apiServiceProvider);
    try {
      final res = await api.getAdminUser();
      if (res.data['status'] == 'success' && mounted) {
        final admin = res.data['admin'];
        Navigator.push(
          context,
          MaterialPageRoute(
            builder: (context) => PrivateChatScreen(
              partner: {'id': admin['id'], 'name': admin['name'] ?? 'Admin'},
            ),
          ),
        ).then((_) => _fetchPrivateChats());
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(
            content: Text('Admin bilan bog\'lanib bo\'lmadi. Qayta urinib ko\'ring.'),
            backgroundColor: Colors.red,
          ),
        );
      }
    }
  }

  void _showStartNewChatDialog() {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (context) {
        return SafeArea(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Padding(
                padding: EdgeInsets.all(16.0),
                child: Text(
                  'Yangi suhbat boshlash',
                  style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold, color: Color(0xFF1A3C2A)),
                ),
              ),
              const Divider(height: 1),
              Expanded(
                child: _privateChats.isEmpty
                    ? const Center(
                        child: Padding(
                          padding: EdgeInsets.all(20.0),
                          child: Text('Tumaningizda boshqa foydalanuvchilar topilmadi.'),
                        ),
                      )
                    : ListView.builder(
                        shrinkWrap: true,
                        itemCount: _privateChats.length,
                        itemBuilder: (context, index) {
                          final user = _privateChats[index];
                          final name = user['name'] ?? 'Fermer';
                          final district = user['district'] ?? '';
                          return ListTile(
                            leading: CircleAvatar(
                              backgroundColor: const Color(0xFFE8F5E9),
                              child: Text(name[0], style: const TextStyle(color: Color(0xFF1A3C2A), fontWeight: FontWeight.bold)),
                            ),
                            title: Text(name, style: const TextStyle(fontWeight: FontWeight.w600)),
                            subtitle: Text(district),
                            onTap: () {
                              Navigator.pop(context);
                              Navigator.push(
                                context,
                                MaterialPageRoute(
                                  builder: (context) => PrivateChatScreen(
                                    partner: {'id': user['id'], 'name': name},
                                  ),
                                ),
                              ).then((_) => _fetchPrivateChats());
                            },
                          );
                        },
                      ),
              ),
            ],
          ),
        );
      },
    );
  }

  Widget _buildInputArea({required VoidCallback onSend}) {
    final currentAuth = ref.read(authProvider);
    final userDistrict = currentAuth.user?['district']?.toString() ?? 'Amudaryo tumani';

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        if (_isEditing)
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            color: Colors.grey[200],
            child: Row(
              children: [
                const Icon(Icons.edit, size: 16, color: Color(0xFF1A3C2A)),
                const SizedBox(width: 8),
                const Expanded(
                  child: Text(
                    'Xabarni tahrirlash...',
                    style: TextStyle(fontSize: 12, fontStyle: FontStyle.italic),
                  ),
                ),
                GestureDetector(
                  onTap: () {
                    setState(() {
                      _isEditing = false;
                      _editingMessageId = null;
                      _messageController.clear();
                    });
                  },
                  child: const Icon(Icons.close, size: 16, color: Colors.grey),
                ),
              ],
            ),
          ),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12.0, vertical: 8.0),
          decoration: BoxDecoration(
            color: Colors.white,
            boxShadow: [
              BoxShadow(
                color: Colors.black.withOpacity(0.05),
                blurRadius: 4,
                offset: const Offset(0, -2),
              )
            ],
          ),
          child: SafeArea(
            child: Row(
              children: [
                Expanded(
                  child: _isRecording
                      ? Row(
                          children: [
                            const Icon(Icons.fiber_manual_record, color: Colors.red, size: 20),
                            const SizedBox(width: 8),
                            const Text(
                              "Ovoz yozilmoqda: ",
                              style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold, fontSize: 13),
                            ),
                            Text(
                              _formatDuration(_recordSeconds),
                              style: const TextStyle(fontWeight: FontWeight.bold, fontSize: 14),
                            ),
                            const Spacer(),
                            IconButton(
                              icon: const Icon(Icons.delete_outline_rounded, color: Colors.grey),
                              onPressed: _cancelRecording,
                            ),
                          ],
                        )
                      : Container(
                          decoration: BoxDecoration(
                            color: Colors.grey[100],
                            borderRadius: BorderRadius.circular(24),
                          ),
                          child: TextField(
                            controller: _messageController,
                            textCapitalization: TextCapitalization.sentences,
                            decoration: const InputDecoration(
                              hintText: 'Xabar yozing...',
                              hintStyle: TextStyle(fontSize: 14, color: Colors.grey),
                              contentPadding: EdgeInsets.symmetric(horizontal: 16.0, vertical: 10.0),
                              border: InputBorder.none,
                            ),
                            maxLines: 4,
                            minLines: 1,
                          ),
                        ),
                ),
                const SizedBox(width: 8),
                CircleAvatar(
                  backgroundColor: _isRecording ? Colors.red : const Color(0xFF1A3C2A),
                  radius: 20,
                  child: _showSendButton || _isEditing
                      ? IconButton(
                          icon: Icon(_isEditing ? Icons.check : Icons.send_rounded, color: Colors.white, size: 18),
                          onPressed: onSend,
                        )
                      : IconButton(
                          icon: Icon(_isRecording ? Icons.check : Icons.mic_rounded, color: Colors.white, size: 18),
                          onPressed: () {
                            if (_isRecording) {
                              _stopAndSendVoice(_tabController.index == 2, userDistrict);
                            } else {
                              _startRecording();
                            }
                          },
                        ),
                ),
              ],
            ),
          ),
        ),
      ],
    );
  }
}
